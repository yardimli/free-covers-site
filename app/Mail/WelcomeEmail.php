<?php

	namespace App\Mail;

	use App\Models\User;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Mail\Mailable;
	use Illuminate\Mail\Mailables\Content;
	use Illuminate\Mail\Mailables\Envelope;
	use Illuminate\Queue\SerializesModels;

	class WelcomeEmail extends Mailable implements ShouldQueue
	{
		use Queueable, SerializesModels;

		public User $user;
		public string $appName;
		public string $logoUrl;
		public string $dashboardUrl;

		/**
		 * Create a new message instance.
		 */
		public function __construct(User $user)
		{
			$this->user = $user;
			$this->appName = config('app.name');
			// Assuming your logo is accessible via this path on your domain
			$this->logoUrl = 'https://freekindlecovers.com/template/assets/img/home/logo-dark.png';
			$this->dashboardUrl = route('dashboard');
		}

		/**
		 * Get the message envelope.
		 */
		public function envelope(): Envelope
		{
			return new Envelope(
				subject: 'Welcome to ' . $this->appName . '!',
			);
		}

		/**
		 * Get the message content definition.
		 */
		public function content(): Content
		{
			return new Content(
				view: 'emails.welcome_html', // Changed from markdown to view
				with: [
					'userName' => $this->user->name,
					'appName' => $this->appName,
					'logoUrl' => $this->logoUrl,
					'dashboardUrl' => $this->dashboardUrl,
					// You can add more image URLs here if needed
					'heroImageUrl' => 'https://freekindlecovers.com/images/free-kindle-covers-layout.jpg', // Example hero image
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
