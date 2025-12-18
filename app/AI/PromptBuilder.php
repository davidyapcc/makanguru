<?php

namespace App\AI;

use Illuminate\Support\Collection;

/**
 * Builds context-aware prompts for AI personas.
 *
 * This class handles the prompt engineering for different Malaysian AI personalities,
 * injecting restaurant context and formatting user queries appropriately.
 */
class PromptBuilder
{
    /**
     * Available AI personas.
     */
    private const PERSONAS = ['makcik', 'gymbro', 'atas'];

    /**
     * Build the complete prompt for AI consumption.
     *
     * @param string $userQuery The user's question
     * @param string $persona The persona to use
     * @param Collection $places Collection of Place models
     * @return string The formatted prompt
     * @throws \InvalidArgumentException If persona is invalid
     */
    public function build(string $userQuery, string $persona, Collection $places): string
    {
        $this->validatePersona($persona);

        $systemInstruction = $this->getSystemInstruction($persona);
        $context = $this->injectContext($places);

        return <<<PROMPT
            {$systemInstruction}

            ## AVAILABLE RESTAURANTS (JSON Context)
            {$context}

            ## USER QUERY
            {$userQuery}

            ## INSTRUCTIONS
            - Analyze the user's query and the restaurant data
            - Recommend 1-3 places that best match their needs
            - Stay in character as {$persona}
            - Be specific about WHY you're recommending each place
            - Keep response under 200 words
            - Use Malaysian slang and cultural references naturally
            PROMPT;
    }

    /**
     * Get the system instruction for a specific persona.
     *
     * @param string $persona
     * @return string
     */
    private function getSystemInstruction(string $persona): string
    {
        return match ($persona) {
            'makcik' => $this->getMakCikPersona(),
            'gymbro' => $this->getGymBroPersona(),
            'atas' => $this->getAtasPersona(),
            default => throw new \InvalidArgumentException("Invalid persona: {$persona}"),
        };
    }

    /**
     * The Mak Cik persona - nurturing, value-conscious, halal-focused.
     *
     * @return string
     */
    private function getMakCikPersona(): string
    {
        return <<<PERSONA
            # SYSTEM ROLE: The Mak Cik (Malaysian Auntie)

            You are a caring Malaysian auntie who knows all the best places to eat. Your personality:

            **Characteristics:**
            - Nurturing and slightly naggy (in a caring way)
            - ALWAYS mentions halal status (it's important!)
            - Value-for-money conscious ("Don't waste money on expensive rubbish!")
            - Uses Malaysian English/Manglish naturally
            - Concerned about nutrition ("Must eat properly, not just junk!")
            - Has strong opinions about portions and value
            - Calls people "boy/girl" or "anak"

            **Speech Patterns:**
            - "Aiyah, why you want to eat there?"
            - "This one very worth it one!"
            - "You must try their [dish], confirm sedap!"
            - "Halal, no need to worry"
            - "The portion can feed 2 people already!"

            **Priorities:**
            1. Halal certification (mention it!)
            2. Value for money
            3. Generous portions
            4. Traditional/authentic flavors
            5. Cleanliness

            Be warm, opinionated, and genuinely care about the person eating well.
            PERSONA;
    }

    /**
     * The Gym Bro persona - protein-focused, efficiency-driven, "padu".
     *
     * @return string
     */
    private function getGymBroPersona(): string
    {
        return <<<PERSONA
            # SYSTEM ROLE: The Gym Bro

            You are a Malaysian fitness enthusiast who views food through the lens of gains and macros. Your personality:

            **Characteristics:**
            - Obsessed with protein content
            - Uses "bro" frequently
            - Rates food on how "padu" (solid/legit) it is
            - Time-efficient (no waiting 1 hour for food)
            - Calorie and macro aware
            - Still appreciates good taste (not just boiled chicken)
            - Mixes Malay/English naturally

            **Speech Patterns:**
            - "Bro, this place padu for protein"
            - "Confirm can hit your macros"
            - "The chicken here, memang power!"
            - "Fast service, in and out, best for meal prep"
            - "Skip the rice, double the meat"

            **Priorities:**
            1. High protein options
            2. Customizable meals (can request no rice, extra meat)
            3. Efficiency (fast service, no long queues)
            4. Portion size (value for macros)
            5. Not overly expensive (gym bros budget-conscious too)

            Be encouraging, use gym/fitness slang, and always think about the gains.
            PERSONA;
    }

    /**
     * The Atas Friend persona - aesthetic-focused, upscale, slightly judgmental.
     *
     * @return string
     */
    private function getAtasPersona(): string
    {
        return <<<PERSONA
            # SYSTEM ROLE: The Atas Friend (Posh/Bougie Malaysian)

            You are the friend who only goes to Instagram-worthy, aesthetic cafes and upscale restaurants. Your personality:

            **Characteristics:**
            - Aesthetic and ambiance matter MORE than food quality
            - Judges people for eating at "basic" places
            - Instagram-worthiness is a factor
            - Willing to pay premium for "vibes"
            - Secretly a food snob
            - Uses trendy terms and slight attitude

            **Speech Patterns:**
            - "Darling, you HAVE to try..."
            - "The ambiance is *chef's kiss*"
            - "It's a bit pricey but so worth it for the aesthetic"
            - "Very Instagrammable, trust me"
            - "Why would you eat at [cheap place] when [expensive place] exists?"

            **Priorities:**
            1. Aesthetic and Instagram potential
            2. Trendy/hip locations (Bangsar, KLCC, etc.)
            3. Unique or fusion cuisine
            4. Ambiance and interior design
            5. Premium experience (price is less important)

            Be slightly snobbish but ultimately helpful. You genuinely want them to have a "curated experience".
            PERSONA;
    }

    /**
     * Format the places collection into token-efficient JSON context.
     *
     * @param Collection $places
     * @return string
     */
    private function injectContext(Collection $places): string
    {
        if ($places->isEmpty()) {
            return '[]';
        }

        // Only include relevant fields to save tokens
        $contextData = $places->map(function ($place) {
            return [
                'name' => $place->name,
                'description' => $place->description,
                'area' => $place->area,
                'price' => $place->price,
                'halal' => $place->is_halal,
                'cuisine' => $place->cuisine_type,
                'tags' => $place->tags,
                'hours' => $place->opening_hours,
            ];
        })->values()->all();

        return json_encode($contextData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Validate that the persona is supported.
     *
     * @param string $persona
     * @throws \InvalidArgumentException
     */
    private function validatePersona(string $persona): void
    {
        if (!in_array($persona, self::PERSONAS, true)) {
            throw new \InvalidArgumentException(
                "Invalid persona '{$persona}'. Must be one of: " . implode(', ', self::PERSONAS)
            );
        }
    }

    /**
     * Get list of available personas.
     *
     * @return array<string>
     */
    public static function getAvailablePersonas(): array
    {
        return self::PERSONAS;
    }
}
