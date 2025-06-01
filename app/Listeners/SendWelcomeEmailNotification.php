<?php

	namespace App\Listeners;

	use Illuminate\Auth\Events\Registered; // Use Laravel's built-in event
	use App\Mail\WelcomeEmail;
	use App\Models\User;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Support\Facades\Mail;
	use Illuminate\Support\Facades\Log;

	class SendWelcomeEmailNotification implements ShouldQueue
	{
		use InteractsWithQueue;

		/**
		 * Create the event listener.
		 */
		public function __construct()
		{
			//
		}

		/**
		 * Handle the event.
		 */
		public function handle(Registered $event): void
		{
			if ($event->user instanceof User) {
				try {
					// Check if this is a new user (email_verified_at might be null initially)
					// Or if you want to ensure it's only sent once, you might add a flag to the user model
					// For now, sending on 'Registered' event is standard for welcome.
					Mail::to($event->user->email)->send(new WelcomeEmail($event->user));
				} catch (\Exception $e) {
					Log::error("Failed to send welcome email to {$event->user->email} after registration: " . $e->getMessage());
				}
			}
		}
	}
