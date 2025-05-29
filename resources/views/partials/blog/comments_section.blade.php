{{-- resources/views/partials/blog/comments_section.blade.php --}}
{{-- This is a static representation based on the template. A dynamic comment system is a larger feature. --}}
<div class="comments-item round-box mb-30 wow fadeInUp">
	<h4 class="mb-35">3 Comments (Static Demo)</h4>
	<div class="comments-content">
		<div>
			<img src="{{ asset('template/assets/img/blog/face-01.png') }}" alt="Commenter Face">
		</div>
		<div class="comments-text">
			<div class="d-flex flex-wrap justify-content-between mb-20">
				<div>
					<a href="#" class="admin-title"> Issac Wise </a>
					<p class="post-time">Jan 01, 2024 at 2:14 pm</p>
				</div>
				<a class="sub-bold-1 comment-reply" data-bs-toggle="collapse" href="#replyColllapse01" role="button" aria-expanded="false" aria-controls="replyColllapse01">Reply <i class="fa-solid fa-arrow-right"></i></a>
			</div>
			<p class="text-area small-2">Wouldn’t it be better practice to use get_the_title(..)
				in this case? directly accessing the post object’s data member would bypass
				applying filters and enforcing protected and private settings, unless that’s
				explicitly desired.</p>
			<div class="collapse" id="replyColllapse01">
				<div class="reply-box">
					<input type="text" class="form-control" placeholder="Write A Comment">
					<button><img src="{{ asset('template/assets/img/blog/Send-Icon.svg') }}" alt="Send"></button>
				</div>
			</div>
			<hr>
			<div class="comments-content">
				<div>
					<img src="{{ asset('template/assets/img/blog/face-02.png') }}" alt="Commenter Face">
				</div>
				<div class="reply-cont">
					<div class="d-flex flex-wrap justify-content-between mb-20">
						<div>
							<a href="#" class="admin-title"> Ellen Ibarra </a>
							<p class="post-time">October 13, 2024</p>
						</div>
						<a class="sub-bold-1 comment-reply" data-bs-toggle="collapse" href="#replyColllapse02" role="button" aria-expanded="false" aria-controls="replyColllapse02">Reply <i class="fa-solid fa-arrow-right"></i></a>
					</div>
					<p class="text-area-2 small-2">Thanks Demo User for Wouldn’t it be better
						practice to use get_the_title.</p>
					<div class="collapse" id="replyColllapse02">
						<div class="reply-box">
							<input type="text" class="form-control" placeholder="Write A Comment">
							<button><img src="{{ asset('template/assets/img/blog/Send-Icon.svg') }}" alt="Send"></button>
						</div>
					</div>
					<hr>
				</div>
			</div>
		</div>
	</div>
	<div class="comments-content">
		<div>
			<img src="{{ asset('template/assets/img/blog/face-03.png') }}" alt="Commenter Face">
		</div>
		<div class="comments-text">
			<div class="d-flex flex-wrap justify-content-between mb-20">
				<div>
					<a href="#" class="admin-title"> Tisa Person </a>
					<p class="post-time">October 13, 2024</p>
				</div>
				<a class="sub-bold-1 comment-reply" data-bs-toggle="collapse" href="#replyColllapse03" role="button" aria-expanded="false" aria-controls="replyColllapse03">Reply <i class="fa-solid fa-arrow-right"></i></a>
			</div>
			<p class="small-2">Wouldn’t it be better practice to use get_the_title(..) in this
				case? directly accessing the post object’s data member would bypass applying
				filters and enforcing protected and private settings, unless that’s explicitly
				desired.</p>
			<div class="collapse" id="replyColllapse03">
				<div class="reply-box">
					<input type="text" class="form-control" placeholder="Write A Comment">
					<button><img src="{{ asset('template/assets/img/blog/Send-Icon.svg') }}" alt="Send"></button>
				</div>
			</div>
		</div>
	</div>
</div>
