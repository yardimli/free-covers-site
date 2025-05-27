{{-- resources/views/terms.blade.php --}}
@extends('layouts.app')

@php
	$footerClass = ''; // Default footer class
@endphp

@section('title', 'Terms & Conditions - Free Kindle Covers')

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
			<h2 class="title wow fadeInUp" data-wow-delay="0.2s">Terms & Conditions</h2>
			<ol class="breadcrumb justify-content-center wow fadeInUp" data-wow-delay="0.3s">
				<li><a href="{{ route('home') }}">Home</a></li>
				<li class="active">Terms & Conditions</li>
			</ol>
		</div>
	</section>
	<!-- breadcrumb area  -->
	
	<!-- terms content area  -->
	<div class="sec_padding bj_privacy_policy" data-bg-color="#f5f5f5">
		<div class="container">
			<div class="page-area">
				<p><em>Last Updated: {{ date('F j, Y') }}</em></p>
				
				<p>Welcome to Free Kindle Covers (freekindlecovers.com). These Terms and Conditions ("Terms") govern your use of our website and services. By accessing or using our website, you agree to be bound by these Terms. If you do not agree with any part of these Terms, you must not use our website or services.</p>
				
				<h3 class="pt-0">1. Definitions</h3>
				<ul>
					<li><strong>“Agreement”</strong> means these Terms & Conditions.</li>
					<li><strong>“Services”</strong> means the services we provide, including our collection of free Kindle cover designs, templates, design elements, and the online cover customization tool, whether obtained directly via our website or otherwise.</li>
					<li><strong>“Website”</strong> means our website, freekindlecovers.com.</li>
					<li><strong>“User”</strong>, <strong>“You”</strong>, <strong>“Your”</strong> means anyone who uses our Services, including general visitors to our Website.</li>
					<li><strong>“We”</strong>, <strong>“Us”</strong>, <strong>“Our”</strong> refers to Free Kindle Covers.</li>
					<li><strong>“Content”</strong> refers to all designs, templates, images, text, graphics, logos, and other materials available on the Website.</li>
				</ul>
				
				<h3>2. Our Services</h3>
				<p>Free Kindle Covers provides a platform for users to browse, download, and customize Kindle cover designs. Our services include:</p>
				<ul>
					<li>A library of pre-designed Kindle cover templates.</li>
					<li>An online design tool to customize these templates with text, images, and other elements.</li>
					<li>The ability to download customized cover designs for personal and commercial use on book publishing platforms.</li>
				</ul>
				<p>While our designs are offered for free, certain premium features or assets might be introduced in the future, which would be clearly indicated.</p>
				
				<h3>3. Eligibility</h3>
				<p>To use our Services, you must:</p>
				<ul>
					<li>Be at least 18 years old or the age of majority in your jurisdiction. If you are under this age, you may only use the Services with the consent and supervision of a parent or legal guardian.</li>
					<li>Agree to comply with all applicable local, state, national, and international laws and regulations.</li>
					<li>Provide accurate information if you choose to register for an account.</li>
				</ul>
				
				<h3>4. User Conduct and Responsibilities</h3>
				<p>You agree not to:</p>
				<ul>
					<li>Use the Services for any unlawful purpose or in violation of these Terms.</li>
					<li>Upload, post, or transmit any content that is infringing, defamatory, obscene, pornographic, abusive, or otherwise objectionable.</li>
					<li>Violate the intellectual property rights of Free Kindle Covers or any third party. This includes not reselling or redistributing our base templates or design elements as standalone assets. You may use the final customized cover for your book.</li>
					<li>Attempt to decompile, reverse engineer, or otherwise attempt to obtain the source code of our design tool or website.</li>
					<li>Introduce viruses, trojans, worms, or other malicious software.</li>
					<li>Use any automated system, such as "bots" or "spiders," to access the Website in a manner that sends more request messages to our servers than a human can reasonably produce in the same period by using a conventional web browser.</li>
					<li>Misrepresent your affiliation with any person or entity.</li>
				</ul>
				
				<h3>5. Intellectual Property Rights</h3>
				<h4>5.1 Our Content</h4>
				<p>All Content provided on the Website, including but not limited to cover designs, templates, graphics, logos, and software, is the property of Free Kindle Covers or its licensors and is protected by copyright and other intellectual property laws. We grant you a limited, non-exclusive, non-transferable, revocable license to use our Content solely for the purpose of creating and using Kindle covers for your books as intended through our Services.</p>
				<p>You may not:</p>
				<ul>
					<li>Resell, redistribute, or sublicense our original templates or design elements as standalone files or as part of a new template collection.</li>
					<li>Use our Content to create products for resale other than as a cover for your own book.</li>
					<li>Claim ownership of our original templates or design elements.</li>
				</ul>
				
				<h4>5.2 Your Content</h4>
				<p>If you upload your own images, text, or other materials ("User Content") to our design tool, you retain all ownership rights to your User Content. However, by uploading User Content, you grant Free Kindle Covers a worldwide, non-exclusive, royalty-free license to use, reproduce, modify (for technical purposes like formatting), and display such User Content solely for the purpose of providing the Services to you (e.g., displaying it in the designer and incorporating it into your final cover).</p>
				<p>You represent and warrant that you have all necessary rights to upload and use your User Content and that it does not infringe upon the rights of any third party.</p>
				
				<h3>6. Disclaimers</h3>
				<p>THE SERVICES AND CONTENT ARE PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT ANY WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, NON-INFRINGEMENT, AND ANY WARRANTIES ARISING OUT OF COURSE OF DEALING OR USAGE OF TRADE.</p>
				<p>FREE KINDLE COVERS DOES NOT WARRANT THAT THE WEBSITE OR SERVICES WILL BE UNINTERRUPTED, ERROR-FREE, SECURE, OR FREE OF VIRUSES OR OTHER HARMFUL COMPONENTS. WE DO NOT WARRANT THE ACCURACY, COMPLETENESS, OR RELIABILITY OF ANY CONTENT OBTAINED THROUGH THE SERVICES.</p>
				<p>YOU ACKNOWLEDGE THAT YOUR USE OF THE SERVICES IS AT YOUR SOLE RISK.</p>
				
				<h3>7. Limitation of Liability</h3>
				<p>TO THE FULLEST EXTENT PERMITTED BY APPLICABLE LAW, FREE KINDLE COVERS, ITS AFFILIATES, OFFICERS, EMPLOYEES, AGENTS, SUPPLIERS, OR LICENSORS SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS OR REVENUES, WHETHER INCURRED DIRECTLY OR INDIRECTLY, OR ANY LOSS OF DATA, USE, GOODWILL, OR OTHER INTANGIBLE LOSSES, RESULTING FROM (A) YOUR ACCESS TO OR USE OF OR INABILITY TO ACCESS OR USE THE SERVICES; (B) ANY CONDUCT OR CONTENT OF ANY THIRD PARTY ON THE SERVICES; (C) ANY CONTENT OBTAINED FROM THE SERVICES; OR (D) UNAUTHORIZED ACCESS, USE, OR ALTERATION OF YOUR TRANSMISSIONS OR CONTENT, WHETHER BASED ON WARRANTY, CONTRACT, TORT (INCLUDING NEGLIGENCE), OR ANY OTHER LEGAL THEORY, WHETHER OR NOT WE HAVE BEEN INFORMED OF THE POSSIBILITY OF SUCH DAMAGE.</p>
				<p>IN NO EVENT SHALL THE AGGREGATE LIABILITY OF FREE KINDLE COVERS EXCEED THE GREATER OF ONE HUNDRED U.S. DOLLARS (USD $100.00) OR THE AMOUNT YOU PAID FREE KINDLE COVERS, IF ANY, IN THE PAST SIX MONTHS FOR THE SERVICES GIVING RISE TO THE CLAIM.</p>
				
				<h3>8. Indemnification</h3>
				<p>You agree to defend, indemnify, and hold harmless Free Kindle Covers and its affiliates, officers, directors, employees, and agents from and against any claims, liabilities, damages, losses, and expenses, including, without limitation, reasonable legal and accounting fees, arising out of or in any way connected with (a) your access to or use of the Services or Content, (b) your User Content, or (c) your violation of these Terms.</p>
				
				<h3>9. Modifications to Terms</h3>
				<p>We reserve the right to modify these Terms at any time. We will notify you of any changes by posting the new Terms on this page and updating the "Last Updated" date. Your continued use of the Services after any such changes constitutes your acceptance of the new Terms. We encourage you to review these Terms periodically for any updates.</p>
				
				<h3>10. Termination</h3>
				<p>We may terminate or suspend your access to our Services immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms. Upon termination, your right to use the Services will immediately cease.</p>
				
				<h3>11. Governing Law</h3>
				<p>These Terms shall be governed and construed in accordance with the laws of [Your Jurisdiction - e.g., the State of California, United States], without regard to its conflict of law provisions. You agree to submit to the personal and exclusive jurisdiction of the courts located within [Your Jurisdiction - e.g., Los Angeles County, California].</p>
				
				<h3>12. Contact Us</h3>
				<p>If you have any questions about these Terms, please contact us at: support@freekindlecovers.com</p>
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
