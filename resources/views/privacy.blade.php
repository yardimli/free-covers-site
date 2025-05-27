{{-- resources/views/privacy.blade.php --}}
@extends('layouts.app')

@php
	$footerClass = ''; // Default footer class
@endphp

@section('title', 'Privacy Policy - Free Kindle Covers')

@section('content')
	<!-- breadcrumb area  -->
	<section class="bj_breadcrumb_area text-center banner_animation_03" data-bg-color="#f5f5f5">
		<div class="bg_one" data-bg-image="{{ asset('template/assets/img/breadcrumb/breadcrumb_banner_bg.png') }}"></div>
		<div class="bd_shape one wow fadeInDown layer" data-wow-delay="0.3s" data-depth="0.5"><img data-parallax='{"y": -50}' src="{{ asset('template/assets/img/breadcrumb/book_left1.png') }}" alt="decorative book">
		</div>
		<div class="bd_shape two wow fadeInUp layer" data-depth="0.6" data-wow-delay="0.4s"><img data-parallax='{"y": 30}' src="{{ asset('template/assets/img/breadcrumb/book-left2.png') }}" alt="decorative book">
		</div>
		<div class="bd_shape three wow fadeInDown layer" data-wow-delay="0.3s" data-depth="0.5"><img data-parallax='{"y": -50}' src="{{ asset('template/assets/img/breadcrumb/plane-1.png') }}" alt="decorative plane">
		</div>
		<div class="bd_shape four wow fadeInUp layer" data-depth="0.6" data-wow-delay="0.4s"><img data-parallax='{"y": 30}' src="{{ asset('template/assets/img/breadcrumb/plan-3.png') }}" alt="decorative plan">
		</div>
		<div class="bd_shape five wow fadeInUp layer" data-depth="0.6" data-wow-delay="0.4s"><img data-parallax='{"y": 80}' src="{{ asset('template/assets/img/breadcrumb/plan-2.png') }}" alt="decorative plan">
		</div>
		<div class="bd_shape six wow fadeInDown layer" data-wow-delay="0.3s" data-depth="0.5"><img data-parallax='{"y": -60}' src="{{ asset('template/assets/img/breadcrumb/book-right.png') }}" alt="decorative book">
		</div>
		<div class="bd_shape seven wow fadeInUp layer" data-depth="0.6" data-wow-delay="0.4s"><img data-parallax='{"x": 50}' src="{{ asset('template/assets/img/breadcrumb/book-right2.png') }}" alt="decorative book">
		</div>
		<div class="container">
			<h2 class="title wow fadeInUp" data-wow-delay="0.2s">Privacy Policy</h2>
			<ol class="breadcrumb justify-content-center wow fadeInUp" data-wow-delay="0.3s">
				<li><a href="{{ route('home') }}">Home</a></li>
				<li class="active">Privacy Policy</li>
			</ol>
		</div>
	</section>
	<!-- breadcrumb area  -->
	
	<!-- privacy content area  -->
	<div class="sec_padding bj_privacy_policy" data-bg-color="#f5f5f5">
		<div class="container">
			<div class="page-area">
				<p><strong>Last Updated: {{ date('F j, Y') }}</strong></p>
				
				<p>Free Kindle Covers ("us", "we", or "our") operates the freekindlecovers.com website (the "Service"). This page informs you of our policies regarding the collection, use, and disclosure of personal data when you use our Service and the choices you have associated with that data.</p>
				<p>We use your data to provide and improve the Service. By using the Service, you agree to the collection and use of information in accordance with this policy. Unless otherwise defined in this Privacy Policy, terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, accessible from freekindlecovers.com/terms-and-conditions.</p>
				
				<h3>1. Information Collection and Use</h3>
				<p>We collect several different types of information for various purposes to provide and improve our Service to you.</p>
				
				<h4>Types of Data Collected</h4>
				<h5>Personal Data</h5>
				<p>While using our Service, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you ("Personal Data"). Personally identifiable information may include, but is not limited to:</p>
				<ul>
					<li>Email address (if you register for an account or subscribe to a newsletter)</li>
					<li>First name and last name (if you register for an account)</li>
					<li>User-generated content (such as customized cover designs, text, or images you upload to the designer tool)</li>
					<li>Cookies and Usage Data</li>
				</ul>
				
				<h5>Usage Data</h5>
				<p>We may also collect information on how the Service is accessed and used ("Usage Data"). This Usage Data may include information such as your computer's Internet Protocol address (e.g. IP address), browser type, browser version, the pages of our Service that you visit, the time and date of your visit, the time spent on those pages, unique device identifiers and other diagnostic data.</p>
				
				<h5>Tracking & Cookies Data</h5>
				<p>We use cookies and similar tracking technologies to track the activity on our Service and hold certain information.</p>
				<p>Cookies are files with a small amount of data which may include an anonymous unique identifier. Cookies are sent to your browser from a website and stored on your device. Tracking technologies also used are beacons, tags, and scripts to collect and track information and to improve and analyze our Service.</p>
				<p>You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our Service.</p>
				<p>Examples of Cookies we use:</p>
				<ul>
					<li><strong>Session Cookies:</strong> We use Session Cookies to operate our Service.</li>
					<li><strong>Preference Cookies:</strong> We use Preference Cookies to remember your preferences and various settings.</li>
					<li><strong>Security Cookies:</strong> We use Security Cookies for security purposes.</li>
					<li><strong>Analytics Cookies:</strong> We may use third-party Service Providers to monitor and analyze the use of our Service (e.g., Google Analytics).</li>
				</ul>
				
				<h3>2. Use of Data</h3>
				<p>Free Kindle Covers uses the collected data for various purposes:</p>
				<ul>
					<li>To provide and maintain our Service</li>
					<li>To notify you about changes to our Service</li>
					<li>To allow you to participate in interactive features of our Service when you choose to do so (e.g., the cover designer)</li>
					<li>To provide customer support</li>
					<li>To gather analysis or valuable information so that we can improve our Service</li>
					<li>To monitor the usage of our Service</li>
					<li>To detect, prevent and address technical issues</li>
					<li>To provide you with news, special offers, and general information about other goods, services, and events which we offer that are similar to those that you have already purchased or enquired about unless you have opted not to receive such information (if applicable).</li>
				</ul>
				
				<h3>3. Legal Basis for Processing Personal Data Under General Data Protection Regulation (GDPR)</h3>
				<p>If you are from the European Economic Area (EEA), Free Kindle Covers' legal basis for collecting and using the personal information described in this Privacy Policy depends on the Personal Data we collect and the specific context in which we collect it.</p>
				<p>Free Kindle Covers may process your Personal Data because:</p>
				<ul>
					<li>We need to perform a contract with you (e.g., when you use our designer tool)</li>
					<li>You have given us permission to do so</li>
					<li>The processing is in our legitimate interests and it's not overridden by your rights</li>
					<li>For payment processing purposes (if applicable in the future)</li>
					<li>To comply with the law</li>
				</ul>
				
				<h3>4. Retention of Data</h3>
				<p>Free Kindle Covers will retain your Personal Data only for as long as is necessary for the purposes set out in this Privacy Policy. We will retain and use your Personal Data to the extent necessary to comply with our legal obligations (for example, if we are required to retain your data to comply with applicable laws), resolve disputes, and enforce our legal agreements and policies.</p>
				<p>Usage Data is generally retained for a shorter period, except when this data is used to strengthen the security or to improve the functionality of our Service, or we are legally obligated to retain this data for longer time periods.</p>
				<p>User-generated content, such as saved designs within our tool, may be retained to allow you to access and modify them, unless you delete them or your account.</p>
				
				<h3>5. Transfer of Data</h3>
				<p>Your information, including Personal Data, may be transferred to — and maintained on — computers located outside of your state, province, country or other governmental jurisdiction where the data protection laws may differ from those from your jurisdiction.</p>
				<p>If you are located outside [Your Country/Region, e.g., United States] and choose to provide information to us, please note that we transfer the data, including Personal Data, to [Your Country/Region, e.g., United States] and process it there.</p>
				<p>Your consent to this Privacy Policy followed by your submission of such information represents your agreement to that transfer.</p>
				<p>Free Kindle Covers will take all steps reasonably necessary to ensure that your data is treated securely and in accordance with this Privacy Policy and no transfer of your Personal Data will take place to an organization or a country unless there are adequate controls in place including the security of your data and other personal information.</p>
				
				<h3>6. Disclosure of Data</h3>
				<h4>Legal Requirements</h4>
				<p>Free Kindle Covers may disclose your Personal Data in the good faith belief that such action is necessary to:</p>
				<ul>
					<li>To comply with a legal obligation</li>
					<li>To protect and defend the rights or property of Free Kindle Covers</li>
					<li>To prevent or investigate possible wrongdoing in connection with the Service</li>
					<li>To protect the personal safety of users of the Service or the public</li>
					<li>To protect against legal liability</li>
				</ul>
				
				<h3>7. Security of Data</h3>
				<p>The security of your data is important to us, but remember that no method of transmission over the Internet, or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.</p>
				
				<h3>8. Your Data Protection Rights Under General Data Protection Regulation (GDPR)</h3>
				<p>If you are a resident of the European Economic Area (EEA), you have certain data protection rights. Free Kindle Covers aims to take reasonable steps to allow you to correct, amend, delete, or limit the use of your Personal Data.</p>
				<p>If you wish to be informed what Personal Data we hold about you and if you want it to be removed from our systems, please contact us.</p>
				<p>In certain circumstances, you have the following data protection rights:</p>
				<ul>
					<li><strong>The right to access, update or to delete the information we have on you.</strong> Whenever made possible, you can access, update or request deletion of your Personal Data directly within your account settings section. If you are unable to perform these actions yourself, please contact us to assist you.</li>
					<li><strong>The right of rectification.</strong> You have the right to have your information rectified if that information is inaccurate or incomplete.</li>
					<li><strong>The right to object.</strong> You have the right to object to our processing of your Personal Data.</li>
					<li><strong>The right of restriction.</strong> You have the right to request that we restrict the processing of your personal information.</li>
					<li><strong>The right to data portability.</strong> You have the right to be provided with a copy of the information we have on you in a structured, machine-readable and commonly used format.</li>
					<li><strong>The right to withdraw consent.</strong> You also have the right to withdraw your consent at any time where Free Kindle Covers relied on your consent to process your personal information.</li>
				</ul>
				<p>Please note that we may ask you to verify your identity before responding to such requests.</p>
				<p>You have the right to complain to a Data Protection Authority about our collection and use of your Personal Data. For more information, please contact your local data protection authority in the European Economic Area (EEA).</p>
				
				<h3>9. Service Providers</h3>
				<p>We may employ third party companies and individuals to facilitate our Service ("Service Providers"), to provide the Service on our behalf, to perform Service-related services or to assist us in analyzing how our Service is used.</p>
				<p>These third parties have access to your Personal Data only to perform these tasks on our behalf and are obligated not to disclose or use it for any other purpose.</p>
				<h4>Analytics</h4>
				<p>We may use third-party Service Providers to monitor and analyze the use of our Service.</p>
				<ul>
					<li>
						<strong>Google Analytics:</strong>
						Google Analytics is a web analytics service offered by Google that tracks and reports website traffic. Google uses the data collected to track and monitor the use of our Service. This data is shared with other Google services. Google may use the collected data to contextualize and personalize the ads of its own advertising network.
						You can opt-out of having made your activity on the Service available to Google Analytics by installing the Google Analytics opt-out browser add-on. The add-on prevents the Google Analytics JavaScript (ga.js, analytics.js, and dc.js) from sharing information with Google Analytics about visits activity.
						For more information on the privacy practices of Google, please visit the Google Privacy & Terms web page: <a href="https://policies.google.com/privacy?hl=en" target="_blank" rel="noopener noreferrer">https://policies.google.com/privacy?hl=en</a>
					</li>
				</ul>
				
				<h3>10. Links to Other Sites</h3>
				<p>Our Service may contain links to other sites that are not operated by us. If you click on a third party link, you will be directed to that third party's site. We strongly advise you to review the Privacy Policy of every site you visit.</p>
				<p>We have no control over and assume no responsibility for the content, privacy policies or practices of any third party sites or services.</p>
				
				<h3>11. Children's Privacy</h3>
				<p>Our Service does not address anyone under the age of 18 ("Children").</p>
				<p>We do not knowingly collect personally identifiable information from anyone under the age of 18. If you are a parent or guardian and you are aware that your Children has provided us with Personal Data, please contact us. If we become aware that we have collected Personal Data from children without verification of parental consent, we take steps to remove that information from our servers.</p>
				
				<h3>12. Changes to This Privacy Policy</h3>
				<p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
				<p>We will let you know via email and/or a prominent notice on our Service, prior to the change becoming effective and update the "last updated" date at the top of this Privacy Policy.</p>
				<p>You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>
				
				<h3>13. Contact Us</h3>
				<p>If you have any questions about this Privacy Policy, please contact us:</p>
				<ul>
					<li>By email: support@freekindlecovers.com</li>
					<li>By visiting this page on our website: freekindlecovers.com/contact (if you create a contact page)</li>
				</ul>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// Ensure parallax and other template JS runs if needed for this page
			if (typeof $ !== 'undefined') {
				if ($(".banner_animation_03").length > 0 && typeof $.fn.parallax === 'function') {
					$(".banner_animation_03").css({"opacity": 1}).parallax({scalarX: 7.0, scalarY: 10.0});
				}
				if (typeof WOW === 'function' && $("body").data("scroll-animation") === true) {
					new WOW({}).init();
				}
			}
		});
	</script>
@endpush
