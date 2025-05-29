{{-- resources/views/partials/blog/comment_form.blade.php --}}
{{-- This is a static representation. Form submission would require backend logic. --}}
<div class="reply-item round-box wow fadeInUp">
	<h4>Leave a Reply (Static Demo)</h4>
	<p class="reply-text mb-35">Your email address will not be published. Required fields are
		marked *</p>
	
	<form class="get_quote_form row" action="#" method="post"> {{-- Point to a real endpoint for dynamic form --}}
		@csrf {{-- Good practice even for static, in case it's made dynamic later --}}
		<div class="col-md-6 form-group">
			<input type="text" class="form-control" id="comment_name" name="comment_name" required>
			<label class="floating-label" for="comment_name">Full Name *</label>
		</div>
		<div class="col-md-6 form-group">
			<input type="email" class="form-control" id="comment_email" name="comment_email" required>
			<label class="floating-label" for="comment_email">Email *</label>
		</div>
		<div class="col-md-12 form-group">
			<input type="text" class="form-control" id="comment_website" name="comment_website">
			<label class="floating-label" for="comment_website">Website (Optional)</label>
		</div>
		<div class="col-md-12 form-group">
			<textarea class="form-control message" id="comment_message_text" name="comment_message" required></textarea>
			<label class="floating-label" for="comment_message_text">Comment type...</label>
		</div>
		<div class="col-md-12 form-group reply-text d-flex">
			<div class="form-check check_input ">
				<input class="form-check-input" type="checkbox" id="comment_save_info" name="comment_save_info" value="yes">
				<label class="form-check-label" for="comment_save_info">Save my name, email, and website in
					this browser for the
					next time I comment.
				</label>
			</div>
		</div>
		<div class="col-md-12 form-group">
			<button class="thm_btn" type="submit">Post Comment</button>
		</div>
	</form>
</div>
