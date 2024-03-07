<html>
<head>
	<script src="https://pi-test.sagepay.com/api/v1/js/sagepay-dropin.js"></script>
	<sscript src="https://pi-test.sagepay.com/api/v1/js/sagepay.js"></sscript>
	<style>
		body * {
			font-family: sans-serif;
		}
		h1 {

		}
		input {
			font-size:12pt;
		}
		#main {
			width: 550px;
			margin: 0 auto;
		}
		#submit-container {
			padding-top:10px;
			float:right;
		}
		input[type=submit] {
			border:none;
			background:indigo;
			padding:10px;
			color:white;
			border-radius:5px;
		}
	</style>
</head>
<body>
<div id="main">
	<h1>Sage Test</h1>
	<h3>KEY: {{ $merchantSessionKey }}</h3>
	<form id="form1">
		<h2>Payment Details</h2>
		<div id="sp-container"></div>
		<div id="submit-container">
			<input type="submit"/>
		</div>
	</form>
	</div>

<script>
	//sagepayCheckout({ merchantSessionKey: '{{ $merchantSessionKey }}' }).form();
	sagepayCheckout({
		merchantSessionKey : '{{ $merchantSessionKey }}',
		containerSelector:  '#sp-container'
	}).form({
		formSelector: '#form1'
	});

</script>
</body>

