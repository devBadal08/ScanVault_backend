<!DOCTYPE html>
<html>
<head>
    <title>Image Viewer</title>
    <style>
        body {
            margin: 0;
            background: black;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        img {
            max-width: 90%;
            max-height: 90%;
        }
        button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 30px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px;
            cursor: pointer;
        }
        .prev { left: 20px; }
        .next { right: 20px; }
    </style>
</head>
<body>

<button class="prev" onclick="prev()">❮</button>
<img id="image">
<button class="next" onclick="next()">❯</button>

<script>
    const images = JSON.parse(atob("{{ request('images') }}"));
    let index = {{ request('index', 0) }};

    function showImage() {
        document.getElementById('image').src = '/storage/' + images[index].path;
    }

    function next() {
        if (index < images.length - 1) {
            index++;
            showImage();
        }
    }

    function prev() {
        if (index > 0) {
            index--;
            showImage();
        }
    }

    showImage();
</script>

</body>
</html>