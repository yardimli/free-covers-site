{{-- resources/views/partials/cover_breadcrumb.blade.php --}}
<section class="bj_breadcrumb_area text-center banner_animation_03" data-bg-color="#f5f5f5">
    <div class="bg_one" data-bg-image="{{ asset('template/assets/img/breadcrumb/breadcrumb_banner_bg.png') }}"></div>
    <div class="container">
        <h2 class="title wow fadeInUp" data-wow-delay="0.2s">
            @if(isset($cover->id))
                <a href="{{ route('covers.show', $cover->id) }}">{{ $cover->name ?: 'Cover Details' }}</a>
            @else
                {{ $cover->name ?: 'Cover Details' }}
            @endif
        </h2>
        <ol class="breadcrumb justify-content-center wow fadeInUp" data-wow-delay="0.3s">
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><a href="{{ route('shop.index') }}">Browse Covers</a></li>
            @if(isset($showCanvasSetup) && $showCanvasSetup)
                <li><a href="{{ route('covers.show', $cover->id) }}">{{ Str::limit($cover->name, 30) ?: 'Cover Details' }}</a></li>
                <li class="active">Canvas Setup</li>
            @else
                <li class="active">{{ Str::limit($cover->name, 30) ?: 'Cover Details' }}</li>
            @endif
        </ol>
    </div>
</section>
