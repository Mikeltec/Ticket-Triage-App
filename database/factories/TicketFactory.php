<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ticketTypes = [
            'technical' => [
                'subjects' => [
                    'Unable to login to my account',
                    'Website keeps crashing when I upload files',
                    'Email notifications not working',
                    'Two-factor authentication issues',
                    'Password reset not working',
                    'API integration errors',
                    'Dashboard loading very slowly',
                    'Mobile app crashes on startup',
                ],
                'bodies' => [
                    'I\'ve been trying to log into my account for the past hour but keep getting an error message saying "Invalid credentials" even though I\'m sure my password is correct. I tried resetting it but didn\'t receive the email.',
                    'Every time I try to upload a file larger than 10MB, the entire website crashes and shows a 500 error. This is preventing me from completing my work. The issue started yesterday.',
                    'I haven\'t been receiving any email notifications for the past week. I checked my spam folder and email settings, but everything looks correct. Other users in my organization are having the same problem.',
                    'I enabled two-factor authentication last week, but now I can\'t get past the 2FA step. The codes from my authenticator app aren\'t being accepted. I need urgent help as I can\'t access important documents.',
                ]
            ],
            'billing' => [
                'subjects' => [
                    'Billing discrepancy on my latest invoice',
                    'Payment method declined',
                    'Refund request for overcharge',
                    'Need invoice for accounting',
                    'Subscription not reflecting upgrade',
                    'Double charged this month',
                ],
                'bodies' => [
                    'I noticed my latest invoice shows charges that don\'t match my subscription plan. I\'m on the Basic plan ($29/month) but was charged $89. Could you please review this and correct the billing?',
                    'My payment was declined yesterday but I have sufficient funds. The card details are correct and I\'ve used this card before. Please help me understand why this happened.',
                    'I was accidentally charged twice for my monthly subscription on December 1st. I need a refund for the duplicate charge of $49.99. My account shows both transactions.',
                    'I need a copy of all invoices from the past year for accounting purposes. Could you please send them to my email or let me know how to download them from my account?',
                ]
            ],
            'feature' => [
                'subjects' => [
                    'Request for dark mode feature',
                    'Export functionality for reports',
                    'Integration with Slack',
                    'Bulk operations for data management',
                    'Custom fields in user profiles',
                    'Advanced filtering options',
                ],
                'bodies' => [
                    'Could you please add a dark mode option to the application? Many users in our team work late hours and the current bright interface is straining on the eyes. This would greatly improve user experience.',
                    'We need the ability to export our reports to PDF and Excel formats. Currently we can only view them online which makes it difficult to share with external stakeholders and include in presentations.',
                    'It would be very helpful to have Slack integration so we can receive notifications directly in our team channels. This would streamline our workflow and reduce the need to constantly check the dashboard.',
                    'We manage hundreds of records and need bulk operations like bulk edit, bulk delete, and bulk status changes. Currently having to do everything individually is very time-consuming.',
                ]
            ],
            'support' => [
                'subjects' => [
                    'How to set up user permissions',
                    'Need help with API documentation',
                    'Training request for new team members',
                    'Best practices for data organization',
                    'Questions about security features',
                ],
                'bodies' => [
                    'I\'m new to the platform and need guidance on setting up user permissions for my team. We have different roles (admin, editor, viewer) and I want to make sure everyone has appropriate access levels.',
                    'I\'m trying to integrate your API into our system but the documentation is unclear about authentication. Could someone help me understand the proper way to authenticate API calls?',
                    'We have 5 new team members joining next week and they\'ll need training on how to use the platform effectively. Do you offer training sessions or have recommended learning resources?',
                    'Our data is getting disorganized as we scale. What are the best practices for organizing projects, tags, and categories? Any tips for maintaining clean data would be appreciated.',
                ]
            ]
        ];

        $type = $this->faker->randomElement(array_keys($ticketTypes));
        $subjects = $ticketTypes[$type]['subjects'];
        $bodies = $ticketTypes[$type]['bodies'];

        return [
            'subject' => $this->faker->randomElement($subjects),
            'body' => $this->faker->randomElement($bodies),
            'status' => $this->faker->randomElement(array_keys(Ticket::STATUSES)),
            'category' => $this->faker->optional(0.7)->randomElement(TicketClassifier::getCategories()),
            'explanation' => $this->faker->optional(0.7)->sentence(12),
            'confidence' => $this->faker->optional(0.7)->randomFloat(2, 0.6, 0.98),
            'note' => $this->faker->optional(0.3)->paragraph(2),
        ];
    }

    /**
     * Create a ticket with AI classification data.
     */
    public function withClassification(): Factory
    {
        return $this->state(function (array $attributes) {
            $category = $this->faker->randomElement(TicketClassifier::getCategories());
            
            return [
                'category' => $category,
                'explanation' => $this->faker->sentence(10),
                'confidence' => $this->faker->randomFloat(2, 0.75, 0.98),
            ];
        });
    }

    /**
     * Create a ticket without classification.
     */
    public function unclassified(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => null,
                'explanation' => null,
                'confidence' => null,
            ];
        });
    }

    /**
     * Create a ticket with a note.
     */
    public function withNote(): Factory
    {
        return $this->state(function (array $attributes) {
            $notes = [
                'Contacted user via phone. Issue resolved by clearing browser cache.',
                'Escalated to development team. Bug confirmed and fix scheduled for next release.',
                'User provided additional details via email. Updating ticket priority.',
                'Waiting for user response. Sent follow-up email with troubleshooting steps.',
                'Issue related to server maintenance. Will be resolved by tomorrow morning.',
                'User satisfied with workaround solution. Monitoring for recurring issues.',
            ];

            return [
                'note' => $this->faker->randomElement($notes),
            ];
        });
    }
}