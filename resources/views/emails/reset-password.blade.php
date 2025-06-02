<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reset Your {{ $appName }} Password</title>
	<style>
      /* Basic Reset */
      body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
      table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
      img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
      table { border-collapse: collapse !important; }
      body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f4f4f4; }

      /* Main Styles */
      .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; /* Ensures border-radius applies to content */ }
      .header { background-color: #007bff; /* Example primary color, adjust to your brand */ padding: 20px; text-align: center; }
      .header img.logo { max-width: 250px; /* Adjust as needed */ height: auto; }
      .content { padding: 30px 20px; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #333333; }
      .content h1 { font-size: 24px; color: #333333; margin-top: 0; }
      .content p { margin-bottom: 15px; }
      .button-container { text-align: center; margin-top: 25px; margin-bottom: 25px; }
      .button { background-color: #dc3545; /* Red for reset action */ color: #ffffff !important; /* Important to override link styles */ padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; }
      .footer { background-color: #eeeeee; padding: 20px; text-align: center; font-family: Arial, sans-serif; font-size: 12px; color: #777777; }
      .footer a { color: #007bff; /* Link color in footer */ text-decoration: none; }
      .hero-image-container { text-align: center; padding: 0; /* No padding if image is edge-to-edge */ }
      .hero-image { max-width: 100%; height: auto; display: block; /* Remove bottom space */ }

      /* Responsive Styles */
      @media screen and (max-width: 600px) {
          .container { width: 100% !important; border-radius: 0; }
      }
	</style>
</head>
<body>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td align="center" style="padding: 20px 0;">
			<!-- Main Container -->
			<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="container">
				<!-- Header with Logo -->
				<tr>
					<td class="header">
						<a href="{{ url('/') }}" target="_blank">
							<img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" class="logo">
						</a>
					</td>
				</tr>
				
				<!-- Optional Hero Image -->
				@if(isset($heroImageUrl) && $heroImageUrl)
					<tr>
						<td class="hero-image-container">
							<img src="{{ $heroImageUrl }}" alt="Password Reset for {{ $appName }}" class="hero-image">
						</td>
					</tr>
				@endif
				
				<!-- Content -->
				<tr>
					<td class="content">
						<h1>Hello, {{ $userName }}!</h1>
						<p>You are receiving this email because we received a password reset request for your {{ $appName }} account.</p>
						<p>Click the button below to reset your password:</p>
						<div class="button-container">
							<a href="{{ $resetUrl }}" target="_blank" class="button">Reset Password</a>
						</div>
						<p>This password reset link will expire in {{ $expirationMinutes }} minutes.</p>
						<p>If you did not request a password reset, no further action is required. You can safely ignore this email.</p>
						<p>If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:</p>
						<p><a href="{{ $resetUrl }}" target="_blank" style="word-break: break-all;">{{ $resetUrl }}</a></p>
						<p>Thanks,<br>The {{ $appName }} Team</p>
					</td>
				</tr>
				
				<!-- Footer -->
				<tr>
					<td class="footer">
						<p>© {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
						<p>
							<a href="{{ route('privacy') }}" target="_blank">Privacy Policy</a> |
							<a href="{{ route('terms') }}" target="_blank">Terms of Service</a>
						</p>
					</td>
				</tr>
			</table>
			<!-- End Main Container -->
		</td>
	</tr>
</table>
</body>
</html>
