{% block stylesheets %}
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/static.css') }}">
{% endblock %}

{% block body %}
    <div class="parallax-container">
        <ul id="scene-image" data-friction-x="0.1" data-friction-y="0.1" data-scalar-x="15" data-scalar-y="15">
            <li class="layer" data-depth="0.70"><img src="{{ asset('images/404/404.png') }}" alt=""></li>
            <li class="layer" data-depth="0.60"> <img src="{{ asset('images/404/error.png') }}" alt=""></li>
            <li class="layer" data-depth="0.70"><img src="{{ asset('images/404/shadow.png') }}" alt=""></li>
            <li class="layer" data-depth="0.80"><img src="{{ asset('images/404/mascot.png') }}" alt=""></li>
            <li class="layer" data-depth="0.60"><img src="{{ asset('images/404/zzz.png') }}" alt=""></li>
        </ul>
</div>
<div class="parallax-container-bottom">
    <ul id="scene-message" data-friction-x="0.1" data-friction-y="0.1" data-scalar-x="15" data-scalar-y="15">
        <li class="align-center layer" data-depth="0.80">
            <h1 class="mrg-top-20">Whoops!</h1>
            <h4 class="light">Unfortunately the page you were looking for could not be found.</h4>
            <h4 class="light">It may be temporarily unavailable, moved or no longer exist.</h4>
            <div class="align-center mrg-top-20">
                <button class="button large basic-white button-rounded-side">
                    Continue Shopping <i class="icon icon-arrow-short-right"></i>
                </button>
            </div>
        </li>
    </ul>
</div>

{% endblock %}

{% block javascripts %}
    <script src="{{ asset('js/bower/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bower/jquery.parallax.min.js') }}"></script>
    <script src="{{ asset('js/src/error-404.js') }}"></script>
{% endblock %}
