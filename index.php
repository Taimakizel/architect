
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Architect — Portfolio</title>
	<style>
		:root{
            --accent:#2b6cb0;
            --muted:#6b7280
        }
		body{
            font-family:Inter,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
            margin:0;
            color:#111;
            background-image:url('uploads/logo.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
        }
		
		.btn{
            display:inline-block;
            padding:10px 14px;
            border-radius:8px;
            border:1px solid hsla(0, 0%, 100%, 0.72);
            background-color:#D2E2E0;
            color:black;
            cursor:pointer;
            font-weight:600;
			margin-top:600px;
			margin-left:700px;
        }
		.btn:hover{
			background:#E5EDEF;
		}
		@media (max-width:600px){
            .hero{
                flex-direction:column;
                align-items:flex-start;
            }
        }
	</style>
</head>
<body>
	<button class="btn" onclick="location.href='homepage.php'">Show more</button>
</body>
</html>
