<style>
    @php
        $hasSidebarTheme = filled($sidebarPrimary ?? null) && filled($sidebarSecondary ?? null);
    @endphp

    body.fi-panel-merchant,
    body.fi-panel-user {
        @foreach ($primary as $shade => $value)
            --primary-{{ $shade }}: {{ $value }};
            --fi-color-primary-{{ $shade }}: {{ $value }};
        @endforeach

        @foreach ($success as $shade => $value)
            --success-{{ $shade }}: {{ $value }};
            --fi-color-success-{{ $shade }}: {{ $value }};
        @endforeach

        @foreach ($warning as $shade => $value)
            --warning-{{ $shade }}: {{ $value }};
            --fi-color-warning-{{ $shade }}: {{ $value }};
        @endforeach

        @foreach ($danger as $shade => $value)
            --danger-{{ $shade }}: {{ $value }};
            --fi-color-danger-{{ $shade }}: {{ $value }};
        @endforeach

        @foreach ($secondary as $shade => $value)
            --secondary-{{ $shade }}: {{ $value }};
            --fi-color-secondary-{{ $shade }}: {{ $value }};
        @endforeach

        @foreach ($default as $shade => $value)
            --default-{{ $shade }}: {{ $value }};
            --fi-color-default-{{ $shade }}: {{ $value }};
        @endforeach

        @if ($hasSidebarTheme)
            --zgn-sidebar-blue: {{ $primary[600] ?? $sidebarPrimary }};
            --zgn-sidebar-green: {{ $secondary[600] ?? $sidebarSecondary }};
            --zgn-sidebar-teal: {{ $secondary[400] ?? ($secondary[500] ?? $sidebarSecondary) }};
        @endif
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
