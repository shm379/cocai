<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
      <!-- فونت وزیر -->
      <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    integrity="sha512-pap/7ua1PFRJbx0zKv5fCRfJ7UnOT1TpGPHZTgbYm5B2jYfM6bgjI0P7MZmVYhzlGtI0tUbAQ8zU9j5POnJv0w=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead
</head>
<body class=" antialiased">
    @inertia
</body>
</html>
