<?php

	namespace App\Mail;

	use App\Models\User;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Mail\Mailable;
	use Illuminate\Mail\Mailables\Content;
	use Illuminate\Mail\Mailables\Envelope;
	use Illuminate\Queue\SerializesModels;

	class PasswordResetEmail extends Mailable implements ShouldQueue
	{
		use Queueable, SerializesModels;

		public User $user;
		public string $resetUrl;
		public string $appName;
		public string $logoUrl;
		public ?string $heroImageUrl; // Made nullable

		/**
		 * Create a new message instance.
		 *
		 * @param User $user
		 * @param string $token
		 */
		public function __construct(User $user, string $token)
		{
			$this->user = $user;
			// Construct the reset URL. The 'password.reset' route typically takes 'token' and 'email' (optional but good for prefill).
			$this->resetUrl = route('password.reset', [
				'token' => $token,
				'email' => $this->user->getEmailForPasswordReset(), // Ensures correct email is used
			]);
			$this->appName = config('app.name', 'Free Kindle Covers');
			$this->logoUrl = config('app.logo_url', 'https://freekindlecovers.com/template/assets/img/home/logo-dark.png');
			$this->heroImageUrl = config('app.email_hero_image_url', 'https://freekindlecovers.com/images/free-kindle-covers-layout.jpg');
		}

		/**
		 * Get the message envelope.
		 */
		public function envelope(): Envelope
		{
			return new Envelope(
				subject: 'Reset Your ' . $this->appName . ' Password',
			);
		}

		/**
		 * Get the message content definition.
		 */
		public function content(): Content
		{
			return new Content(
				view: 'emails.reset-password',
				with: [
					'userName' => $this->user->name,
					'appName' => $this->appName,
					'logoUrl' => $this->logoUrl,
					'resetUrl' => $this->resetUrl,
					'heroImageUrl' => $this->heroImageUrl,
					'expirationMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
				]
			);
		}

		/**
		 * Get the attachments for the message.
		 *
		 * @return array<int, \Illuminate\Mail\Mailables\Attachment>
		 */
		public function attachments(): array
		{
			return [];
		}
	}
