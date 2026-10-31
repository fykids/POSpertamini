<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="flex">
        @include('layouting.ui.sidebar')
        <div class="flex-1 flex flex-col min-w-0">
            @include('layouting.ui.nav')
        </div>
    </div>
</body>

</html>