<!DOCTYPE html>
<html>
<head>
    <title>Import Data PRITI</title>
</head>
<body>

    <h2>Upload Data PRITI</h2>

    @if(session('success'))
        <div>
            {{ session('success') }}
        </div>
    @endif

    <form action="/import" method="POST" enctype="multipart/form-data">
        @csrf

        <input type="file" name="file" required>

        <button type="submit">
            Upload
        </button>
    </form>

</body>
</html>