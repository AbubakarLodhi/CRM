<style>
    .fi-color-primary {
        @foreach ($primary as $shade => $value)
             --color-{{ $shade }}: {{ $value }};
        @endforeach
    }

    .fi-color-success {
        @foreach ($success as $shade => $value)
             --color-{{ $shade }}: {{ $value }};
        @endforeach
    }

    .fi-color-warning {
        @foreach ($warning as $shade => $value)
             --color-{{ $shade }}: {{ $value }};
        @endforeach
    }

    .fi-color-danger {
        @foreach ($danger as $shade => $value)
             --color-{{ $shade }}: {{ $value }};
        @endforeach
    }

    .fi-color-secondary {
        @foreach ($secondary as $shade => $value)
             --color-{{ $shade }}: {{ $value }};
        @endforeach
    }

    .fi-color-default {
        @foreach ($default as $shade => $value)
             --color-{{ $shade }}: {{ $value }};
        @endforeach
    }



 {{--/* LIGHT MODE */--}}
 {{--   body.fi-panel-merchant {--}}
 {{--       @foreach ($primaryLight as $shade => $value)--}}
 {{--              --fi-color-primary- {{ $shade }}: {{ $value }};--}}
 {{--       @endforeach--}}

 {{--       @foreach ($successLight as $shade => $value)--}}
 {{--              --fi-color-success- {{ $shade }}: {{ $value }};--}}
 {{--   @endforeach--}}

 {{--   }--}}

 {{--   /* DARK MODE */--}}
 {{--   html.dark body.fi-panel-merchant {--}}
 {{--       @foreach ($primaryDark as $shade => $value)--}}
 {{--            --fi-color-primary- {{ $shade }}: {{ $value }};--}}
 {{--       @endforeach--}}

 {{--       @foreach ($successDark as $shade => $value)--}}
 {{--            --fi-color-success- {{ $shade }}: {{ $value }};--}}
 {{--   @endforeach--}}

 {{--   }--}}
</style>
