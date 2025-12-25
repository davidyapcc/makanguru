<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service for generating shareable social media cards from AI recommendations.
 *
 * This service creates beautiful, persona-themed social cards that users can
 * share on social media platforms. Cards are generated as SVG images with
 * persona-specific styling and branding.
 *
 * @package App\Services
 */
class SocialCardService
{
    /**
     * Card dimensions (optimized for social media).
     */
    private const CARD_WIDTH = 1200;
    private const CARD_HEIGHT = 630;

    /**
     * Persona color schemes.
     */
    private const PERSONA_COLORS = [
        'makcik' => [
            'background' => '#FEF3C7',
            'primary' => '#92400E',
            'accent' => '#D97706',
            'text' => '#78350F',
        ],
        'gymbro' => [
            'background' => '#DBEAFE',
            'primary' => '#1E40AF',
            'accent' => '#3B82F6',
            'text' => '#1E3A8A',
        ],
        'atas' => [
            'background' => '#FCE7F3',
            'primary' => '#9F1239',
            'accent' => '#F472B6',
            'text' => '#831843',
        ],
        'tauke' => [
            'background' => '#FEF9C3',
            'primary' => '#CA8A04',
            'accent' => '#EAB308',
            'text' => '#713F12',
        ],
        'matmotor' => [
            'background' => '#F3E8FF',
            'primary' => '#7C3AED',
            'accent' => '#A78BFA',
            'text' => '#5B21B6',
        ],
        'corporate' => [
            'background' => '#F3F4F6',
            'primary' => '#374151',
            'accent' => '#6B7280',
            'text' => '#1F2937',
        ],
    ];

    /**
     * Persona avatar emojis.
     */
    private const PERSONA_AVATARS = [
        'makcik' => '👵',
        'gymbro' => '💪',
        'atas' => '💅',
        'tauke' => '🧧',
        'matmotor' => '🏍️',
        'corporate' => '💼',
    ];

    /**
     * Persona display names.
     */
    private const PERSONA_NAMES = [
        'makcik' => 'The Mak Cik',
        'gymbro' => 'The Gym Bro',
        'atas' => 'The Atas Friend',
        'tauke' => 'The Tauke',
        'matmotor' => 'The Mat Motor',
        'corporate' => 'The Corporate Slave',
    ];

    /**
     * Generate a social media card from a recommendation.
     *
     * @param string $recommendation The AI recommendation text
     * @param string $persona The persona used ('makcik', 'gymbro', 'atas', 'tauke', 'matmotor', 'corporate')
     * @param string $userQuery The user's original query
     * @return string The file path to the generated card
     */
    public function generateCard(string $recommendation, string $persona, string $userQuery): string
    {
        $colors = self::PERSONA_COLORS[$persona] ?? self::PERSONA_COLORS['makcik'];
        $avatar = self::PERSONA_AVATARS[$persona] ?? '👵';
        $personaName = self::PERSONA_NAMES[$persona] ?? 'MakanGuru';

        // Truncate recommendation if too long (for visual appeal)
        // Limit to ~350 chars to fit within 5-6 lines
        $displayRecommendation = Str::limit($recommendation, 350, '...');
        $displayQuery = Str::limit($userQuery, 80, '...');

        // Generate SVG
        $svg = $this->generateSvg(
            $displayRecommendation,
            $displayQuery,
            $personaName,
            $avatar,
            $colors
        );

        // Save to storage
        $filename = 'social-cards/' . Str::uuid() . '.svg';
        Storage::disk('public')->put($filename, $svg);

        return $filename;
    }

    /**
     * Generate the SVG markup for the social card.
     *
     * @param string $recommendation The recommendation text
     * @param string $query The user query
     * @param string $personaName The persona display name
     * @param string $avatar The persona avatar emoji
     * @param array<string, string> $colors The color scheme
     * @return string The SVG markup
     */
    private function generateSvg(
        string $recommendation,
        string $query,
        string $personaName,
        string $avatar,
        array $colors
    ): string {
        // Escape special characters for SVG
        $recommendation = htmlspecialchars($recommendation, ENT_XML1, 'UTF-8');
        $query = htmlspecialchars($query, ENT_XML1, 'UTF-8');
        $personaName = htmlspecialchars($personaName, ENT_XML1, 'UTF-8');

        // Word wrap for better display (shorter lines for better readability)
        $wrappedRecommendation = $this->wrapText($recommendation, 85);
        $wrappedQuery = $this->wrapText($query, 60);

        // Limit lines to prevent overflow
        $recommendationLines = explode("\n", $wrappedRecommendation);
        $recommendationLines = array_slice($recommendationLines, 0, 6); // Max 6 lines
        $wrappedRecommendation = implode("\n", $recommendationLines);

        $queryLines = explode("\n", $wrappedQuery);
        $queryLines = array_slice($queryLines, 0, 2); // Max 2 lines
        $wrappedQuery = implode("\n", $queryLines);

        $width = self::CARD_WIDTH;
        $height = self::CARD_HEIGHT;

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="{$width}" height="{$height}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bg-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:{$colors['background']};stop-opacity:1" />
      <stop offset="100%" style="stop-color:{$colors['accent']};stop-opacity:0.3" />
    </linearGradient>

    <filter id="shadow">
      <feDropShadow dx="0" dy="4" stdDeviation="8" flood-opacity="0.1"/>
    </filter>
  </defs>

  <!-- Background -->
  <rect width="100%" height="100%" fill="url(#bg-gradient)"/>

  <!-- Content Container -->
  <g filter="url(#shadow)">
    <!-- Header -->
    <rect x="50" y="50" width="1100" height="90" fill="white" rx="15" opacity="0.95"/>
    <text x="80" y="110" font-family="Arial, sans-serif" font-size="42" fill="{$colors['primary']}" font-weight="bold">
      {$avatar} {$personaName}
    </text>

    <!-- Query Section -->
    <rect x="50" y="160" width="1100" height="90" fill="white" rx="12" opacity="0.9"/>
    <text x="80" y="190" font-family="Arial, sans-serif" font-size="16" fill="{$colors['text']}" font-weight="600">
      YOUR QUESTION:
    </text>
    {$this->generateTextLines($wrappedQuery, 80, 215, 20, $colors['primary'], true)}

    <!-- Recommendation Section -->
    <rect x="50" y="270" width="1100" height="240" fill="white" rx="12" opacity="0.95"/>
    <text x="80" y="300" font-family="Arial, sans-serif" font-size="16" fill="{$colors['text']}" font-weight="600">
      RECOMMENDATION:
    </text>
    {$this->generateTextLines($wrappedRecommendation, 80, 330, 19, $colors['primary'], false)}

    <!-- Footer -->
    <text x="600" y="560" font-family="Arial, sans-serif" font-size="26" fill="{$colors['accent']}" font-weight="bold" text-anchor="middle">
      MakanGuru.my
    </text>
    <text x="600" y="590" font-family="Arial, sans-serif" font-size="15" fill="{$colors['text']}" text-anchor="middle">
      AI-Powered Malaysian Food Recommendations
    </text>
  </g>
</svg>
SVG;
    }

    /**
     * Wrap text to a specified width.
     *
     * @param string $text The text to wrap
     * @param int $width The maximum line width
     * @return string The wrapped text
     */
    private function wrapText(string $text, int $width): string
    {
        return wordwrap($text, $width, "\n", true);
    }

    /**
     * Generate SVG text elements for multi-line text.
     *
     * @param string $text The text to render
     * @param int $x The x position
     * @param int $y The starting y position
     * @param int $fontSize The font size
     * @param string $color The text color
     * @param bool $italic Whether to use italic style
     * @return string The SVG text elements
     */
    private function generateTextLines(string $text, int $x, int $y, int $fontSize, string $color, bool $italic = false): string
    {
        $lines = explode("\n", $text);
        $svg = '';
        $lineHeight = $fontSize + 6;
        $fontStyle = $italic ? 'italic' : 'normal';

        foreach ($lines as $index => $line) {
            // Skip empty lines
            if (trim($line) === '') {
                continue;
            }

            $currentY = $y + ($index * $lineHeight);

            // Add quote marks for query text
            if ($italic) {
                $line = '"' . trim($line) . '"';
            }

            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="Arial, sans-serif" font-size="%d" fill="%s" font-style="%s">%s</text>' . "\n    ",
                $x,
                $currentY,
                $fontSize,
                $color,
                $fontStyle,
                htmlspecialchars($line, ENT_XML1, 'UTF-8')
            );
        }

        return $svg;
    }

    /**
     * Delete a generated card from storage.
     *
     * @param string $filename The card filename
     * @return bool True if deleted successfully
     */
    public function deleteCard(string $filename): bool
    {
        return Storage::disk('public')->delete($filename);
    }

    /**
     * Get the public URL for a generated card.
     *
     * @param string $filename The card filename
     * @return string The public URL
     */
    public function getCardUrl(string $filename): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($filename);
    }

    /**
     * Clean up old social cards.
     *
     * @param int $days Number of days to keep cards. 0 to delete all.
     * @return int Number of cards deleted
     */
    public function cleanupOldCards(int $days = 7): int
    {
        $files = Storage::disk('public')->files('social-cards');
        $deleted = 0;

        // If days is 0, we delete everything, so set cutoff to now (or future)
        // If days > 0, we set cutoff to now - days
        $cutoffTime = $days === 0
            ? now()->addHour()->timestamp
            : now()->subDays($days)->timestamp;

        foreach ($files as $file) {
            if (Storage::disk('public')->lastModified($file) < $cutoffTime) {
                if (Storage::disk('public')->delete($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
