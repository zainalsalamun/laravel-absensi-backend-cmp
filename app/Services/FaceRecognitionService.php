<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * FaceRecognitionService
 * 
 * Self-hosted face recognition using image feature extraction.
 * This implementation uses histogram comparison and structural image analysis
 * to match faces without requiring external paid APIs.
 * 
 * NOTE: For production use with higher accuracy, consider integrating:
 * - Python microservice with face_recognition library (free, uses dlib)
 * - OpenCV with deep learning models
 * - AWS Rekognition (paid)
 */
class FaceRecognitionService
{
    /**
     * Similarity threshold for face matching (0-100)
     * Higher = more strict matching
     */
    const SIMILARITY_THRESHOLD = 65;

    /**
     * Enroll a face for a user
     * 
     * @param int $userId User ID
     * @param string $imagePath Path to the image file
     * @return array Result with face_embedding data
     */
    public function enrollFace($userId, $imagePath)
    {
        try {
            // Extract features from the image
            $features = $this->extractImageFeatures($imagePath);
            
            if (!$features) {
                return [
                    'success' => false,
                    'message' => 'Failed to extract face features from image'
                ];
            }

            // Create face embedding data
            $faceEmbedding = [
                'user_id' => $userId,
                'histogram' => $features['histogram'],
                'color_moments' => $features['color_moments'],
                'texture_features' => $features['texture_features'],
                'image_hash' => $features['image_hash'],
                'created_at' => now()->toISOString(),
            ];

            return [
                'success' => true,
                'message' => 'Face enrolled successfully',
                'embedding' => $faceEmbedding,
                'features' => $features
            ];

        } catch (\Exception $e) {
            Log::error('Face enrollment failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Face enrollment failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify if a photo matches the enrolled face
     * 
     * @param array $enrolledEmbedding The stored face embedding
     * @param string $imagePath Path to the verification image
     * @return array Verification result
     */
    public function verifyFace($enrolledEmbedding, $imagePath)
    {
        try {
            // Extract features from verification image
            $features = $this->extractImageFeatures($imagePath);
            
            if (!$features) {
                return [
                    'success' => false,
                    'message' => 'Failed to extract face features',
                    'similarity' => 0,
                    'is_match' => false
                ];
            }

            // Calculate similarity score
            $similarity = $this->calculateSimilarity($enrolledEmbedding, $features);
            
            // Determine if it's a match based on threshold
            $isMatch = $similarity >= self::SIMILARITY_THRESHOLD;

            return [
                'success' => true,
                'message' => $isMatch ? 'Face verified successfully' : 'Face does not match',
                'similarity' => round($similarity, 2),
                'is_match' => $isMatch,
                'threshold' => self::SIMILARITY_THRESHOLD
            ];

        } catch (\Exception $e) {
            Log::error('Face verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Face verification failed: ' . $e->getMessage(),
                'similarity' => 0,
                'is_match' => false
            ];
        }
    }

    /**
     * Calculate similarity between enrolled face and new face
     * 
     * @param array $enrolled Stored face embedding
     * @param array $current Current face features
     * @return float Similarity score (0-100)
     */
    protected function calculateSimilarity($enrolled, $current)
    {
        $scores = [];

        // 1. Histogram comparison (weight: 40%)
        if (isset($enrolled['histogram']) && isset($current['histogram'])) {
            $histogramScore = $this->compareHistograms(
                $enrolled['histogram'], 
                $current['histogram']
            );
            $scores['histogram'] = $histogramScore * 0.4;
        }

        // 2. Color moments comparison (weight: 30%)
        if (isset($enrolled['color_moments']) && isset($current['color_moments'])) {
            $colorScore = $this->compareColorMoments(
                $enrolled['color_moments'], 
                $current['color_moments']
            );
            $scores['color'] = $colorScore * 0.3;
        }

        // 3. Texture features comparison (weight: 20%)
        if (isset($enrolled['texture_features']) && isset($current['texture_features'])) {
            $textureScore = $this->compareTextureFeatures(
                $enrolled['texture_features'], 
                $current['texture_features']
            );
            $scores['texture'] = $textureScore * 0.2;
        }

        // 4. Image hash comparison (weight: 10%)
        if (isset($enrolled['image_hash']) && isset($current['image_hash'])) {
            $hashScore = $this->compareImageHashes(
                $enrolled['image_hash'], 
                $current['image_hash']
            );
            $scores['hash'] = $hashScore * 0.1;
        }

        // Calculate weighted average
        $totalScore = array_sum($scores);
        
        return min(100, max(0, $totalScore * 100));
    }

    /**
     * Compare color histograms using Bhattacharyya coefficient
     */
    protected function compareHistograms($hist1, $hist2)
    {
        if (empty($hist1) || empty($hist2)) {
            return 0;
        }

        $minLength = min(count($hist1), count($hist2));
        $similarity = 0;

        for ($i = 0; $i < $minLength; $i++) {
            $similarity += sqrt($hist1[$i] * $hist2[$i]);
        }

        return $similarity;
    }

    /**
     * Compare color moments (mean, std, skewness)
     */
    protected function compareColorMoments($moments1, $moments2)
    {
        if (empty($moments1) || empty($moments2)) {
            return 0;
        }

        $distances = [];
        
        foreach (['mean', 'std', 'skewness'] as $moment) {
            if (isset($moments1[$moment]) && isset($moments2[$moment])) {
                $distance = $this->euclideanDistance(
                    $moments1[$moment], 
                    $moments2[$moment]
                );
                $distances[] = $distance;
            }
        }

        if (empty($distances)) {
            return 0;
        }

        $avgDistance = array_sum($distances) / count($distances);
        
        // Convert distance to similarity (inverse)
        return max(0, 1 - ($avgDistance / 255));
    }

    /**
     * Compare texture features
     */
    protected function compareTextureFeatures($tex1, $tex2)
    {
        if (empty($tex1) || empty($tex2)) {
            return 0;
        }

        $distance = $this->euclideanDistance($tex1, $tex2);
        
        return max(0, 1 - ($distance / 1000));
    }

    /**
     * Compare image hashes using Hamming distance
     */
    protected function compareImageHashes($hash1, $hash2)
    {
        if (empty($hash1) || empty($hash2)) {
            return 0;
        }

        $hash1 = (string) $hash1;
        $hash2 = (string) $hash2;
        
        if (strlen($hash1) !== strlen($hash2)) {
            return 0;
        }

        $diff = 0;
        $length = strlen($hash1);
        
        for ($i = 0; $i < $length; $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $diff++;
            }
        }

        return 1 - ($diff / $length);
    }

    /**
     * Calculate Euclidean distance between two arrays
     */
    protected function euclideanDistance($arr1, $arr2)
    {
        $sum = 0;
        $length = min(count($arr1), count($arr2));
        
        for ($i = 0; $i < $length; $i++) {
            $diff = ($arr1[$i] ?? 0) - ($arr2[$i] ?? 0);
            $sum += $diff * $diff;
        }
        
        return sqrt($sum);
    }

    /**
     * Extract features from an image file
     * 
     * @param string $imagePath Path to image file
     * @return array|null Extracted features or null on failure
     */
    protected function extractImageFeatures($imagePath)
    {
        try {
            // Check if file exists
            if (!file_exists($imagePath)) {
                Log::error("Image file not found: {$imagePath}");
                return null;
            }

            // Get image info
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return null;
            }

            // Create image resource based on type
            $image = $this->createImageResource($imagePath, $imageInfo['mime']);
            if (!$image) {
                return null;
            }

            // Resize to standard size for comparison
            $width = imagesx($image);
            $height = imagesy($image);
            
            $standardWidth = 64;
            $standardHeight = 64;
            $standardImage = imagecreatetruecolor($standardWidth, $standardHeight);
            imagecopyresampled($standardImage, $image, 0, 0, 0, 0, $standardWidth, $standardHeight, $width, $height);

            // Extract features
            $histogram = $this->extractColorHistogram($standardImage);
            $colorMoments = $this->extractColorMoments($standardImage);
            $textureFeatures = $this->extractTextureFeatures($standardImage);
            $imageHash = $this->generateImageHash($standardImage);

            // Clean up
            imagedestroy($image);
            imagedestroy($standardImage);

            return [
                'histogram' => $histogram,
                'color_moments' => $colorMoments,
                'texture_features' => $textureFeatures,
                'image_hash' => $imageHash,
                'width' => $width,
                'height' => $height,
            ];

        } catch (\Exception $e) {
            Log::error('Feature extraction failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create image resource from file
     */
    protected function createImageResource($filePath, $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/gif':
                return imagecreatefromgif($filePath);
            case 'image/webp':
                return imagecreatefromwebp($filePath);
            default:
                Log::error("Unsupported image type: {$mimeType}");
                return null;
        }
    }

    /**
     * Extract color histogram (RGB, 16 bins per channel)
     */
    protected function extractColorHistogram($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $bins = 16;
        $histogram = array_fill(0, $bins * 3, 0);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($image, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                $histogram[intval($r / (256 / $bins))]++;
                $histogram[$bins + intval($g / (256 / $bins))]++;
                $histogram[$bins * 2 + intval($b / (256 / $bins))]++;
            }
        }

        // Normalize
        $totalPixels = $width * $height;
        return array_map(function($val) use ($totalPixels) {
            return $val / $totalPixels;
        }, $histogram);
    }

    /**
     * Extract color moments (mean, std, skewness) for RGB channels
     */
    protected function extractColorMoments($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        
        $pixels = ['r' => [], 'g' => [], 'b' => []];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($image, $x, $y);
                $pixels['r'][] = ($color >> 16) & 0xFF;
                $pixels['g'][] = ($color >> 8) & 0xFF;
                $pixels['b'][] = $color & 0xFF;
            }
        }

        $moments = [];
        foreach ($pixels as $channel => $values) {
            $moments[$channel] = [
                'mean' => $this->calculateMean($values),
                'std' => $this->calculateStdDev($values),
                'skewness' => $this->calculateSkewness($values),
            ];
        }

        return [
            'mean' => [
                $moments['r']['mean'],
                $moments['g']['mean'],
                $moments['b']['mean']
            ],
            'std' => [
                $moments['r']['std'],
                $moments['g']['std'],
                $moments['b']['std']
            ],
            'skewness' => [
                $moments['r']['skewness'],
                $moments['g']['skewness'],
                $moments['b']['skewness']
            ],
        ];
    }

    /**
     * Extract simple texture features (edge density)
     */
    protected function extractTextureFeatures($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        
        $edgeCount = 0;
        $totalPixels = 0;

        // Simple edge detection using Sobel-like filter
        for ($y = 1; $y < $height - 1; $y++) {
            for ($x = 1; $x < $width - 1; $x++) {
                $center = $this->getGrayscale($image, $x, $y);
                $right = $this->getGrayscale($image, $x + 1, $y);
                $bottom = $this->getGrayscale($image, $x, $y + 1);
                
                $gradientX = abs($right - $center);
                $gradientY = abs($bottom - $center);
                $gradient = sqrt($gradientX * $gradientX + $gradientY * $gradientY);
                
                if ($gradient > 30) { // Edge threshold
                    $edgeCount++;
                }
                $totalPixels++;
            }
        }

        $edgeDensity = $totalPixels > 0 ? $edgeCount / $totalPixels : 0;
        
        return [$edgeDensity * 1000]; // Scale for better comparison
    }

    /**
     * Generate perceptual hash (average hash)
     */
    protected function generateImageHash($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Convert to grayscale and calculate average
        $total = 0;
        $count = 0;
        $pixels = [];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $gray = $this->getGrayscale($image, $x, $y);
                $pixels[] = $gray;
                $total += $gray;
                $count++;
            }
        }

        $average = $count > 0 ? $total / $count : 128;
        
        // Generate hash based on whether pixel is above or below average
        $hash = '';
        foreach ($pixels as $pixel) {
            $hash .= ($pixel >= $average) ? '1' : '0';
        }

        return $hash;
    }

    /**
     * Get grayscale value at position
     */
    protected function getGrayscale($image, $x, $y)
    {
        $color = imagecolorat($image, $x, $y);
        $r = ($color >> 16) & 0xFF;
        $g = ($color >> 8) & 0xFF;
        $b = $color & 0xFF;
        
        return intval(0.299 * $r + 0.587 * $g + 0.114 * $b);
    }

    /**
     * Calculate mean of array
     */
    protected function calculateMean($values)
    {
        if (empty($values)) return 0;
        return array_sum($values) / count($values);
    }

    /**
     * Calculate standard deviation
     */
    protected function calculateStdDev($values)
    {
        if (count($values) < 2) return 0;
        
        $mean = $this->calculateMean($values);
        $sumSquares = 0;
        
        foreach ($values as $val) {
            $sumSquares += ($val - $mean) ** 2;
        }
        
        return sqrt($sumSquares / (count($values) - 1));
    }

    /**
     * Calculate skewness
     */
    protected function calculateSkewness($values)
    {
        if (count($values) < 3) return 0;
        
        $mean = $this->calculateMean($values);
        $std = $this->calculateStdDev($values);
        
        if ($std == 0) return 0;
        
        $sumCubed = 0;
        foreach ($values as $val) {
            $sumCubed += (($val - $mean) / $std) ** 3;
        }
        
        $n = count($values);
        return ($n / (($n - 1) * ($n - 2))) * $sumCubed;
    }
}
