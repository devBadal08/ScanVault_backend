<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 Unauthorized</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-10 rounded shadow-md text-center max-w-md">
        <h1 class="text-6xl font-bold text-red-600 mb-6">403</h1>
        <p class="text-xl text-gray-700 mb-8">🚫 You’re not authorized to view this page.</p>
        <a href="{{ url()->previous() }}"
           class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded transition">
            ← Go Back
        </a>
    </div>
</body>
</html>
