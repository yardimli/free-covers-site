{{-- resources/views/faq.blade.php --}}
@extends('layouts.app')

@php
	$footerClass = ''; // Default footer class
@endphp

@section('title', 'FAQ - Free Kindle Covers')

@push('styles')
	<style>
      .faq-section {
          margin-bottom: 2rem;
      }
      .accordion-button:not(.collapsed) {
          color: var(--bs-primary);
          background-color: var(--bs-light); /* Or a light primary color */
      }
      .accordion-button:focus {
          box-shadow: none;
          border-color: rgba(0,0,0,.125);
      }
      .accordion-header h2 {
          margin-bottom: 0;
          font-size: 1.25rem;
      }
      .accordion-body ul {
          padding-left: 1.5rem;
      }
      .accordion-body ul li {
          margin-bottom: 0.5rem;
      }
	</style>
@endpush

@section('content')
	@include('partials.faq_breadcrumb')
	
	<section class="faq_area sec_padding">
		<div class="container">
			<div class="row">
				<div class="col-lg-10 offset-lg-1">
					<h2 class="text-center mb-5">Frequently Asked Questions</h2>
					
					{{-- General Section --}}
					<div class="faq-section">
						<h3 class="mb-3">General</h3>
						<div class="accordion" id="faqGeneral">
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingGeneralOne">
									<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneralOne" aria-expanded="true" aria-controls="collapseGeneralOne">
										What is Free Kindle Covers?
									</button>
								</h2>
								<div id="collapseGeneralOne" class="accordion-collapse collapse show" aria-labelledby="headingGeneralOne" data-bs-parent="#faqGeneral">
									<div class="accordion-body">
										Free Kindle Covers is a platform offering a wide variety of professionally designed book cover templates that you can customize for free. We aim to provide authors with high-quality cover options for their eBooks and print books.
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingGeneralTwo">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneralTwo" aria-expanded="false" aria-controls="collapseGeneralTwo">
										Are the covers really free?
									</button>
								</h2>
								<div id="collapseGeneralTwo" class="accordion-collapse collapse" aria-labelledby="headingGeneralTwo" data-bs-parent="#faqGeneral">
									<div class="accordion-body">
										Yes, all cover templates available on our site are free to use for both personal and commercial projects, subject to our <a href="{{ route('terms') }}">Terms of Service</a>.
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingGeneralThree">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneralThree" aria-expanded="false" aria-controls="collapseGeneralThree">
										Do I need an account?
									</button>
								</h2>
								<div id="collapseGeneralThree" class="accordion-collapse collapse" aria-labelledby="headingGeneralThree" data-bs-parent="#faqGeneral">
									<div class="accordion-body">
										You can browse covers without an account. However, an account is required to customize and save your designs to your dashboard and to use the "Favorites" feature.
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingGeneralFour">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneralFour" aria-expanded="false" aria-controls="collapseGeneralFour">
										What can I use these covers for?
									</button>
								</h2>
								<div id="collapseGeneralFour" class="accordion-collapse collapse" aria-labelledby="headingGeneralFour" data-bs-parent="#faqGeneral">
									<div class="accordion-body">
										You can use the customized covers for your eBooks (e.g., Kindle, Kobo), print books (e.g., KDP Print, IngramSpark), and promotional materials for your book. We will later support square covers for audible.
									</div>
								</div>
							</div>
						</div>
					</div>
					
					{{-- Browsing Section --}}
					<div class="faq-section">
						<h3 class="mb-3">Browsing Covers</h3>
						<div class="accordion" id="faqBrowsing">
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingBrowsingOne">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBrowsingOne" aria-expanded="false" aria-controls="collapseBrowsingOne">
										How do I find covers?
									</button>
								</h2>
								<div id="collapseBrowsingOne" class="accordion-collapse collapse" aria-labelledby="headingBrowsingOne" data-bs-parent="#faqBrowsing">
									<div class="accordion-body">
										You can find covers by:
										<ul>
											<li>Browsing the "New Arrivals" section on the homepage.</li>
											<li>Exploring genres in the "Browse By Genres" section on the homepage.</li>
											<li>Using the "Browse Covers" link in the main menu, which allows filtering by category and searching by keywords.</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingBrowsingTwo">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBrowsingTwo" aria-expanded="false" aria-controls="collapseBrowsingTwo">
										What are "Available Styles" on the cover detail page?
									</button>
								</h2>
								<div id="collapseBrowsingTwo" class="accordion-collapse collapse" aria-labelledby="headingBrowsingTwo" data-bs-parent="#faqBrowsing">
									<div class="accordion-body">
										"Available Styles" refer to different pre-designed text and element layouts (also known as templates) that can be applied to the same base cover image. This allows you to choose a visual style that best fits your book's title and author name for a particular cover design.
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingBrowsingThree">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBrowsingThree" aria-expanded="false" aria-controls="collapseBrowsingThree">
										How do I see a larger preview of a cover, its full version, or its 3D mockup?
									</button>
								</h2>
								<div id="collapseBrowsingThree" class="accordion-collapse collapse" aria-labelledby="headingBrowsingThree" data-bs-parent="#faqBrowsing">
									<div class="accordion-body">
										On the cover detail page, below the main cover image, you'll find smaller thumbnail images for "Full Cover" (if available) and "3D Mockup" (if available). Clicking on these thumbnails will open a larger preview in a modal window.
									</div>
								</div>
							</div>
						</div>
					</div>
					
					{{-- Kindle vs Print Cover Section --}}
					<div class="faq-section">
						<h3 class="mb-3">Kindle vs. Print Cover</h3>
						<div class="accordion" id="faqKindlePrint">
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingKindlePrintOne">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKindlePrintOne" aria-expanded="false" aria-controls="collapseKindlePrintOne">
										What's the difference between customizing a Kindle cover and a Print cover?
									</button>
								</h2>
								<div id="collapseKindlePrintOne" class="accordion-collapse collapse" aria-labelledby="headingKindlePrintOne" data-bs-parent="#faqKindlePrint">
									<div class="accordion-body">
										<ul>
											<li><strong>Kindle Cover:</strong> This is typically just the front cover design. The designer will often default to standard eBook dimensions (e.g., 1600x2560 pixels). You'll customize the text and elements for the front view.</li>
											<li><strong>Print Cover:</strong> This is a full wrap-around cover that includes the front, spine, and back. The dimensions are more complex as they depend on your book's trim size (e.g., 6x9 inches), page count, and paper type (which affect spine width). You'll customize elements across all three sections.</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingKindlePrintTwo">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKindlePrintTwo" aria-expanded="false" aria-controls="collapseKindlePrintTwo">
										Why do I need to go to a "Setup Page" for print covers?
									</button>
								</h2>
								<div id="collapseKindlePrintTwo" class="accordion-collapse collapse" aria-labelledby="headingKindlePrintTwo" data-bs-parent="#faqKindlePrint">
									<div class="accordion-body">
										The "Setup Page" for print covers is crucial for calculating the correct dimensions for your full wrap-around cover. It allows you to input your book's specific trim size, page count, and paper type. This information is used to determine the precise width of the spine and the overall dimensions of the canvas in the designer, ensuring your printed cover fits perfectly.
									</div>
								</div>
							</div>
						</div>
					</div>
					
					{{-- Print Cover Setup Page Section --}}
					<div class="faq-section">
						<h3 class="mb-3">Print Cover Setup Page</h3>
						<div class="accordion" id="faqPrintSetup">
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingPrintSetupOne">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrintSetupOne" aria-expanded="false" aria-controls="collapsePrintSetupOne">
										What information do I need for the print cover setup?
									</button>
								</h2>
								<div id="collapsePrintSetupOne" class="accordion-collapse collapse" aria-labelledby="headingPrintSetupOne" data-bs-parent="#faqPrintSetup">
									<div class="accordion-body">
										You'll need to select or input:
										<ul>
											<li><strong>Units:</strong> Inches or millimeters.</li>
											<li><strong>Preset Size (Front Cover):</strong> Choose from common trim sizes (e.g., 5x8", 6x9") or select "Custom" to input your own dimensions.</li>
											<li><strong>Page Count:</strong> The number of pages in your book.</li>
											<li><strong>Paper Type:</strong> White or Cream, as this affects paper thickness and thus spine width.</li>
										</ul>
										You can also choose to enter the spine width directly in pixels if you know it.
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingPrintSetupTwo">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrintSetupTwo" aria-expanded="false" aria-controls="collapsePrintSetupTwo">
										How is the spine width calculated?
									</button>
								</h2>
								<div id="collapsePrintSetupTwo" class="accordion-collapse collapse" aria-labelledby="headingPrintSetupTwo" data-bs-parent="#faqPrintSetup">
									<div class="accordion-body">
										The spine width is automatically calculated based on your specified page count and selected paper type (white or cream, as they have different thicknesses). The setup page uses industry-standard calculations for this. Alternatively, you can switch to "Enter Pixels" mode and input a specific spine width if you have predetermined this value.
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingPrintSetupThree">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrintSetupThree" aria-expanded="false" aria-controls="collapsePrintSetupThree">
										What does the preview on the setup page show?
									</button>
								</h2>
								<div id="collapsePrintSetupThree" class="accordion-collapse collapse" aria-labelledby="headingPrintSetupThree" data-bs-parent="#faqPrintSetup">
									<div class="accordion-body">
										The preview on the setup page provides an approximate visual representation of how your front cover, spine, and back cover will be laid out relative to each other based on the dimensions you've selected or calculated. The selected cover's image is often used as a faint background to give context.
									</div>
								</div>
							</div>
						</div>
					</div>
					
					{{-- Designer Functions Section --}}
					<div class="faq-section">
						<h3 class="mb-3">Designer Functions</h3>
						<div class="accordion" id="faqDesigner">
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingDesignerOne">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDesignerOne" aria-expanded="false" aria-controls="collapseDesignerOne">
										What are the icons on the far left of the designer (Icon Bar)?
									</button>
								</h2>
								<div id="collapseDesignerOne" class="accordion-collapse collapse" aria-labelledby="headingDesignerOne" data-bs-parent="#faqDesigner">
									<div class="accordion-body">
										The Icon Bar on the left provides quick access to different asset and tool panels:
										<ul>
											<li><strong>Elements:</strong> Add decorative shapes, icons, or other graphical elements.</li>
											<li><strong>Overlays:</strong> Add full-image overlays (e.g., textures, color washes).</li>
											<li><strong>Upload:</strong> Upload your own images to use in your design.</li>
											<li><strong>Layers:</strong> Manage all the individual items (text, images, shapes) on your canvas.</li>
											<li><strong>Canvas Background:</strong> Change the background color of the canvas or make it transparent.</li>
											<li>Other icons include Save, Undo, Redo, and Download.</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingDesignerTwo">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDesignerTwo" aria-expanded="false" aria-controls="collapseDesignerTwo">
										How do I add elements, overlays, or apply templates?
									</button>
								</h2>
								<div id="collapseDesignerTwo" class="accordion-collapse collapse" aria-labelledby="headingDesignerTwo" data-bs-parent="#faqDesigner">
									<div class="accordion-body">
										Click the corresponding icon in the left Icon Bar (e.g., Elements, Overlays, Templates). A panel will slide out. You can then browse or search within that panel. Clicking on an item in the panel will add it to your canvas (for elements/overlays) or apply it to your current design (for templates).
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingDesignerThree">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDesignerThree" aria-expanded="false" aria-controls="collapseDesignerThree">
										What is the Layers panel for?
									</button>
								</h2>
								<div id="collapseDesignerThree" class="accordion-collapse collapse" aria-labelledby="headingDesignerThree" data-bs-parent="#faqDesigner">
									<div class="accordion-body">
										The Layers panel (icon looks like stacked squares) lists all the individual items (text boxes, images, shapes) currently on your design canvas. From here, you can:
										<ul>
											<li>Select layers by clicking on them.</li>
											<li>Reorder layers by dragging them up or down (affects which elements appear in front of others).</li>
											<li>Toggle the visibility of a layer (show/hide).</li>
											<li>Lock/unlock layers to prevent accidental changes.</li>
											<li>Rename layers by double-clicking their name.</li>
											<li>Delete layers.</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingDesignerFour">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDesignerFour" aria-expanded="false" aria-controls="collapseDesignerFour">
										When does the Inspector Panel (right side) appear and what can I do with it?
									</button>
								</h2>
								<div id="collapseDesignerFour" class="accordion-collapse collapse" aria-labelledby="headingDesignerFour" data-bs-parent="#faqDesigner">
									<div class="accordion-body">
										The Inspector Panel appears on the right side of the designer when you select a layer (an image, text box, or shape) on your canvas or from the Layers panel. It displays all the customizable properties for that specific layer.
										<br><br>
										Depending on the layer type, you can:
										<ul>
											<li><strong>General:</strong> Adjust position (X, Y), width, height, opacity, rotation, and scale. Align the layer to the canvas. Clone or delete the layer.</li>
											<li><strong>Text Layers:</strong> Change text content, font family, font size, style (bold, italic, underline), color, horizontal and vertical alignment, letter spacing, line height, padding, add a shadow (color, blur, offset, angle), and add a background (color, opacity, corner radius).</li>
											<li><strong>Image Layers:</strong> Adjust blend mode (e.g., overlay, multiply) and apply various filters (brightness, contrast, saturation, grayscale, sepia, hue-rotate, blur).</li>
										</ul>
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingDesignerFive">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDesignerFive" aria-expanded="false" aria-controls="collapseDesignerFive">
										How do I change text content or fonts?
									</button>
								</h2>
								<div id="collapseDesignerFive" class="accordion-collapse collapse" aria-labelledby="headingDesignerFive" data-bs-parent="#faqDesigner">
									<div class="accordion-body">
										First, select the text layer you want to edit by clicking on it on the canvas or in the Layers panel. The Inspector Panel will appear on the right.
										<ul>
											<li><strong>To change text content:</strong> Look for the "Content" textarea in the "Text" section of the Inspector Panel and type your new text.</li>
											<li><strong>To change fonts:</strong> In the "Text" section of the Inspector Panel, click on the font family input field. A font picker will appear, allowing you to choose from a list of available fonts.</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					{{-- Designer Save and Download Section --}}
					<div class="faq-section">
						<h3 class="mb-3">Designer: Save & Download</h3>
						<div class="accordion" id="faqSaveDownload">
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingSaveDownloadOne">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSaveDownloadOne" aria-expanded="false" aria-controls="collapseSaveDownloadOne">
										How do I save my design?
									</button>
								</h2>
								<div id="collapseSaveDownloadOne" class="accordion-collapse collapse" aria-labelledby="headingSaveDownloadOne" data-bs-parent="#faqSaveDownload">
									<div class="accordion-body">
										If you are logged in, you can save your design by clicking the "Save My Design" icon (cloud with an upload arrow) in the left Icon Bar. You will be prompted to enter a name for your design. Once saved, it will be available in your User Dashboard.
										<br>(Admins also have an option to save the design as a local <code>.json</code> file).
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingSaveDownloadTwo">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSaveDownloadTwo" aria-expanded="false" aria-controls="collapseSaveDownloadTwo">
										How do I download my finished cover?
									</button>
								</h2>
								<div id="collapseSaveDownloadTwo" class="accordion-collapse collapse" aria-labelledby="headingSaveDownloadTwo" data-bs-parent="#faqSaveDownload">
									<div class="accordion-body">
										Click the "Download Image (PNG)" icon (downward arrow) in the left Icon Bar. This will generate and download your current canvas design as a PNG image file.
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingSaveDownloadThree">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSaveDownloadThree" aria-expanded="false" aria-controls="collapseSaveDownloadThree">
										What format is the downloaded image?
									</button>
								</h2>
								<div id="collapseSaveDownloadThree" class="accordion-collapse collapse" aria-labelledby="headingSaveDownloadThree" data-bs-parent="#faqSaveDownload">
									<div class="accordion-body">
										The primary download function in the designer provides your cover as a <strong>PNG</strong> file. PNGs are excellent for graphics as they support transparency and offer high quality.
									</div>
								</div>
							</div>
						</div>
					</div>
					
					{{-- User Dashboard Section --}}
					<div class="faq-section">
						<h3 class="mb-3">User Dashboard</h3>
						<div class="accordion" id="faqDashboard">
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingDashboardOne">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboardOne" aria-expanded="false" aria-controls="collapseDashboardOne">
										How do I access my saved designs or favorites?
									</button>
								</h2>
								<div id="collapseDashboardOne" class="accordion-collapse collapse" aria-labelledby="headingDashboardOne" data-bs-parent="#faqDashboard">
									<div class="accordion-body">
										Log in to your account and click on the user icon in the top right of the header, or navigate directly to your Dashboard. You'll find sections for "My Saved Designs" and "My Favorites".
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingDashboardTwo">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboardTwo" aria-expanded="false" aria-controls="collapseDashboardTwo">
										How can I edit a design I previously saved?
									</button>
								</h2>
								<div id="collapseDashboardTwo" class="accordion-collapse collapse" aria-labelledby="headingDashboardTwo" data-bs-parent="#faqDashboard">
									<div class="accordion-body">
										In your Dashboard, under "My Saved Designs," find the design you want to edit. Click the "Edit" button on its card. This will open the design in the online designer, ready for you to make further changes.
									</div>
								</div>
							</div>
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingDashboardThree">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboardThree" aria-expanded="false" aria-controls="collapseDashboardThree">
										The dashboard offers a JPG download for my saved design, but the designer downloads as PNG. Why?
									</button>
								</h2>
								<div id="collapseDashboardThree" class="accordion-collapse collapse" aria-labelledby="headingDashboardThree" data-bs-parent="#faqDashboard">
									<div class="accordion-body">
										<ul>
											<li><strong>Dashboard JPG Download:</strong> This is a quick download of the <em>preview image</em> that was generated when you saved your design. JPGs are generally smaller in file size and are convenient for quick previews or sharing.</li>
											<li><strong>Designer PNG Download:</strong> The main download function within the designer provides a PNG file. PNGs are lossless, support transparency (if your canvas background is set to transparent), and are often preferred for final, high-quality graphics for publishing platforms.</li>
										</ul>
										For the best quality for your final book cover upload (e.g., to KDP), using the PNG downloaded directly from the designer is generally recommended.
									</div>
								</div>
							</div>
						</div>
					</div>
				
				</div>
			</div>
		</div>
	</section>
@endsection

@push('scripts')
	{{-- Add page-specific scripts if needed --}}
@endpush
