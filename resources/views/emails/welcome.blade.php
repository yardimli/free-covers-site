<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Welcome to {{ $appName }}!</title>
	<style>
      /* Basic Reset */
      body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
      table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
      img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
      table { border-collapse: collapse !important; }
      body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f4f4f4; }

      /* Main Styles */
      .container {
          width: 100%;
          max-width: 600px;
          margin: 0 auto;
          background-color: #ffffff;
          border-radius: 8px;
          overflow: hidden; /* Ensures border-radius applies to content */
      }
      .header {
          background-color: #007bff; /* Example primary color, adjust to your brand */
          padding: 20px;
          text-align: center;
      }
      .header img.logo {
          max-width: 250px; /* Adjust as needed */
          height: auto;
      }
      .content {
          padding: 30px 20px;
          font-family: Arial, sans-serif;
          font-size: 16px;
          line-height: 1.6;
          color: #333333;
      }
      .content h1 {
          font-size: 24px;
          color: #333333;
          margin-top: 0;
      }
      .content p {
          margin-bottom: 15px;
      }
      .button-container {
          text-align: center;
          margin-top: 25px;
          margin-bottom: 25px;
      }
      .button {
          background-color: #28a745; /* Example button color, adjust */
          color: #ffffff !important; /* Important to override link styles */
          padding: 12px 25px;
          text-decoration: none;
          border-radius: 5px;
          font-weight: bold;
          display: inline-block;
      }
      .footer {
          background-color: #eeeeee;
          padding: 20px;
          text-align: center;
          font-family: Arial, sans-serif;
          font-size: 12px;
          color: #777777;
      }
      .footer a {
          color: #007bff; /* Link color in footer */
          text-decoration: none;
      }
      .hero-image-container {
          text-align: center;
          padding: 0; /* No padding if image is edge-to-edge */
      }
      .hero-image {
          max-width: 100%;
          height: auto;
          display: block; /* Remove bottom space */
      }

      /* Responsive Styles */
      @media screen and (max-width: 600px) {
          .container {
              width: 100% !important;
              border-radius: 0;
          }
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
						<a href="https://freekindlecovers.com/" target="_blank">
							<img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" class="logo">
						</a>
					</td>
				</tr>
				
				<!-- Optional Hero Image -->
				@if(isset($heroImageUrl) && $heroImageUrl)
					<tr>
						<td class="hero-image-container">
							<img src="{{ $heroImageUrl }}" alt="Welcome to {{ $appName }}" class="hero-image">
						</td>
					</tr>
				@endif
				
				<!-- Content -->
				<tr>
					<td class="content">
						<h1>Welcome, {{ $userName }}!</h1>
						<p>We're thrilled to have you join the {{ $appName }} community. Get ready to explore a world of amazing cover designs and unleash your creativity.</p>
						<p>With your new account, you can:</p>
						<ul>
							<li>Browse and customize stunning cover templates.</li>
							<li>Save your favorite designs.</li>
							<li>Create and manage your own unique covers.</li>
						</ul>
						<p>Click the button below to get started and head to your dashboard:</p>
						<div class="button-container">
							<a href="{{ $dashboardUrl }}" target="_blank" class="button">Go to Your Dashboard</a>
						</div>
						<p>If you have any questions, feel free to <a href="https://freekindlecovers.com/contact-us" target="_blank">contact our support team</a>.</p>
						<p>Happy designing!</p>
						<p>Best regards,<br>The {{ $appName }} Team</p>
					</td>
				</tr>
				
				<!-- Footer -->
				<tr>
					<td class="footer">
						<p>© {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
						<p>
							<a href="https://freekindlecovers.com/privacy-policy" target="_blank">Privacy Policy</a> |
							<a href="https://freekindlecovers.com/terms-and-conditions" target="_blank">Terms of Service</a>
						</p>
						{{-- You can add an unsubscribe link here if necessary for marketing emails,
								 but for a transactional welcome email, it's usually not required. --}}
					</td>
				</tr>
			</table>
			<!-- End Main Container -->
		</td>
	</tr>
</table>
</body>
</html>
