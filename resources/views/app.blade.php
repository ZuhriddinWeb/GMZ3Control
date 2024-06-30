<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>GMZ</title>

        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        {{-- <link rel="stylesheet" href="{{mix('/css/app.css')}}"> --}}
        {{-- <link rel="stylesheet" href="{{ asset('awesome/css/all.min.css') }}"> --}}
                <link
        href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:ital,wght@0,400;1,700&display=swap"
        rel="stylesheet"
        />
        <link
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet"
        />
        <!-- <link rel="stylesheet" href="{{mix('/css/app.css')}}"> -->
        <link rel="stylesheet" href="{{ asset('awesome/css/all.min.css') }}">
        @vite('resources/css/app.css')

    </head>
    <body>
       <div id="app"></div>
       @vite('resources/js/app.js')
    </body>
</html>
