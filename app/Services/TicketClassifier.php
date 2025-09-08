<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Exception;

class TicketClassifier
{
    /**
     * Available ticket categories.
     */
    private const CATEGORIES = [
        'technical_support',
        'billing_inquiry',
        'feature_request',
        'bug_report',
        'account_access',
        'general_inquiry',
        'complaint',
        'hardware_issue',
        'software_issue',
        'network_connectivity',
    ];

    /**
     * System prompt for OpenAI classification.
     */
    private const SYSTEM_PROMPT = '
You are a help desk ticket classification system. Analyze the provided ticket and classify it into one of these categories:

Categories:
- technical_support: General technical help or guidance
- billing_inquiry: Questions about billing, payments, invoices
- feature_request: Requests for new features or enhancements
- bug_report: Reports of software bugs or issues
- account_access: Problems logging in or accessing accounts
- general_inquiry: General questions or information requests
- complaint: Customer complaints or dissatisfaction
- hardware_issue: Problems with physical hardware
- software_issue: Problems with software functionality
- network_connectivity: Internet or network connection problems

You must respond with valid JSON in this exact format:
{
    "category": "one_of_the_categories_above",
    "explanation": "Brief explanation of why this category was chosen",
    "confidence": 0.95
}

The confidence should be a number between 0 and 1, where 1 is completely confident and 0 is not confident at all.
';

    /**
     * Classify a ticket using OpenAI or return dummy data if disabled.
     */
    public function classify(string $subject, string $body): array
    {
        // Check if OpenAI classification is enabled
        if (!config('openai.classify_enabled', true)) {
            return $this->getDummyClassification();
        }

        try {
            return $this->classifyWithOpenAI($subject, $body);
        } catch (Exception $e) {
            Log::error('OpenAI classification failed', [
                'error' => $e->getMessage(),
                'subject' => $subject,
            ]);

            // Return dummy classification on error
            return $this->getDummyClassification();
        }
    }

    /**
     * Classify ticket using OpenAI API.
     */
    private function classifyWithOpenAI(string $subject, string $body): array
    {
        $userPrompt = "Subject: {$subject}\n\nBody: {$body}";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 150,
            'temperature' => 0.1, // Low temperature for consistent classification
        ]);

        $content = $response->choices[0]->message->content;

        // Parse JSON response
        $classification = json_decode($content, true);

        if (!$classification || !$this->isValidClassification($classification)) {
            throw new Exception('Invalid classification response from OpenAI');
        }

        return [
            'category' => $classification['category'],
            'explanation' => $classification['explanation'],
            'confidence' => (float) $classification['confidence'],
        ];
    }

    /**
     * Get dummy classification data when OpenAI is disabled.
     */
    private function getDummyClassification(): array
    {
        $categories = self::CATEGORIES;
        $randomCategory = $categories[array_rand($categories)];

        $explanations = [
            'technical_support' => 'Appears to be a technical support request based on keywords.',
            'billing_inquiry' => 'Contains billing or payment related terminology.',
            'feature_request' => 'User is requesting a new feature or enhancement.',
            'bug_report' => 'Describes unexpected behavior or system errors.',
            'account_access' => 'User having trouble accessing their account.',
            'general_inquiry' => 'General question or information request.',
            'complaint' => 'Expresses dissatisfaction with service or product.',
            'hardware_issue' => 'Describes problems with physical hardware.',
            'software_issue' => 'Reports software functionality problems.',
            'network_connectivity' => 'Network or connectivity related issue.',
        ];

        return [
            'category' => $randomCategory,
            'explanation' => $explanations[$randomCategory] . ' (Dummy classification - OpenAI disabled)',
            'confidence' => round(mt_rand(70, 95) / 100, 2), // Random confidence 0.70-0.95
        ];
    }

    /**
     * Validate classification response format.
     */
    private function isValidClassification(array $classification): bool
    {
        if (!isset($classification['category'], $classification['explanation'], $classification['confidence'])) {
            return false;
        }

        if (!in_array($classification['category'], self::CATEGORIES)) {
            return false;
        }

        $confidence = (float) $classification['confidence'];
        if ($confidence < 0 || $confidence > 1) {
            return false;
        }

        return true;
    }

    /**
     * Get available categories.
     */
    public static function getCategories(): array
    {
        return self::CATEGORIES;
    }
}