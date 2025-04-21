<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Текущий трек из Яндекс Музыки</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="p-8 max-w-md w-full">
    {{--<h1 class="text-2xl font-bold text-center mb-6">Текущий трек из Яндекс Музыки</h1>--}}

    @if($track)
        <div class="flex items-center space-x-4 mb-6">
            <img src="{{ $track['image_url'] }}" alt="Обложка" class="w-24 h-24 rounded-lg">
            <div>
                <h2 class="text-xl font-semibold">{{ $track['title'] }}</h2>
                <p class="text-gray-600">{{ $track['artists'][0]['name'] }}</p>
                <p class="text-sm text-gray-500">Альбом: {{ $track['albums'][0]['title'] }}</p>
            </div>
        </div>

        <div class="flex justify-between items-center text-sm text-gray-500">
            <span>{{ date("i:s", $progress / 1000) }}</span>
            <span>{{ date("i:s", $duration / 1000) }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
            <div class="bg-yellow-100 h-2.5 rounded-full"
                 style="width: {{$progress / $duration * 100}}%"></div>
        </div>

        <div class="mt-6 flex justify-center space-x-4">
            <a href="https://music.yandex.ru/album/{{ $track['albums'][0]['id'] }}/track/{{ $track['id'] }}" target="_blank"
               class="px-4 py-2 bg-yellow-100 text-white rounded hover:bg-blue-700 transition">
                Слушать в Яндекс Музыке
            </a>
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-gray-500">Не удалось получить информацию о текущем треке</p>
            <p class="text-sm text-gray-400 mt-2">Попробуйте обновить страницу позже</p>
        </div>
    @endif
</div>
</body>
</html>
