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
    ];

    /**
     * Persona avatar emojis.
     */
    private const PERSONA_AVATARS = [
        'makcik' => '👵',
        'gymbro' => '💪',
        'atas' => '💅',
    ];

    /**
     * Persona display names.
     */
    private const PERSONA_NAMES = [
        'makcik' => 'The Mak Cik',
        'gymbro' => 'The Gym Bro',
        'atas' => 'The Atas Friend',
    ];

    /**
     * Generate a social media card from a recommendation.
     *
     * @param string $recommendation The AI recommendation text
     * @param string $persona The persona used ('makcik', 'gymbro', 'atas')
     * @param string $userQuery The user's original query
     * @return string The file path to the generated card
     */
    public function generateCard(string $recommendation, string $persona, string $userQuery): string
    {
        $colors = self::PERSONA_COLORS[$persona] ?? self::PERSONA_COLORS['makcik'];
        $avatar = self::PERSONA_AVATARS[$persona] ?? '👵';
        $personaName = self::PERSONA_NAMES[$persona] ?? 'MakanGuru';

        // Truncate recommendation if too long (for visual appeal)
        $displayRecommendation = Str::limit($recommendation, 280, '...');
        $displayQuery = Str::limit($userQuery, 100, '...');

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

        // Word wrap for better display
        $wrappedRecommendation = $this->wrapText($recommendation, 70);
        $wrappedQuery = $this->wrapText($query, 50);

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
    <rect x="60" y="60" width="1080" height="100" fill="white" rx="20" opacity="0.95"/>
    <text x="100" y="125" font-family="Arial, sans-serif" font-size="48" fill="{$colors['primary']}" font-weight="bold">
      {$avatar} {$personaName}
    </text>

    <!-- Query Section -->
    <rect x="60" y="180" width="1080" height="auto" fill="white" rx="15" opacity="0.9"/>
    <text x="100" y="230" font-family="Arial, sans-serif" font-size="20" fill="{$colors['text']}" font-weight="600">
      YOUR QUESTION:
    </text>
    <text x="100" y="270" font-family="Arial, sans-serif" font-size="24" fill="{$colors['primary']}" font-style="italic">
      "{$wrappedQuery}"
    </text>

    <!-- Recommendation Section -->
    <rect x="60" y="320" width="1080" height="auto" fill="white" rx="15" opacity="0.95"/>
    <text x="100" y="370" font-family="Arial, sans-serif" font-size="18" fill="{$colors['text']}" font-weight="600">
      RECOMMENDATION:
    </text>
    {$this->generateTextLines($wrappedRecommendation, 100, 410, 22, $colors['primary'])}

    <!-- Footer -->
    <text x="600" y="580" font-family="Arial, sans-serif" font-size="28" fill="{$colors['accent']}" font-weight="bold" text-anchor="middle">
      MakanGuru.my
    </text>
    <text x="600" y="610" font-family="Arial, sans-serif" font-size="16" fill="{$colors['text']}" text-anchor="middle">
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
     * @return string The SVG text elements
     */
    private function generateTextLines(string $text, int $x, int $y, int $fontSize, string $color): string
    {
        $lines = explode("\n", $text);
        $svg = '';
        $lineHeight = $fontSize + 8;

        foreach ($lines as $index => $line) {
            $currentY = $y + ($index * $lineHeight);
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="Arial, sans-serif" font-size="%d" fill="%s">%s</text>' . "\n    ",
                $x,
                $currentY,
                $fontSize,
                $color,
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
        return Storage::disk('public')->url($filename);
    }

    /**
     * Clean up old social cards (older than 7 days).
     *
     * @return int Number of cards deleted
     */
    public function cleanupOldCards(): int
    {
        $files = Storage::disk('public')->files('social-cards');
        $deleted = 0;
        $cutoffTime = now()->subDays(7)->timestamp;

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
