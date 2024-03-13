@extends('app')

@section('title', 'Terms and Conditions')

@section('content')

	<div class="container">
		<h1>Wholesale Terms and Conditions</h1>

		<h4>Wholesale Guidelines</h4>

		<ul>
			<li>
				No refunds will be given, only replacements will be issued for faulty devices. In the event a replacement is not available within 14 days a credit will be issued.
			</li>
			<li>
				Postage is non refundable only the devices themselves will be credited.
			</li>
			<li>
				Returns will only be accepted for stock flagged for not as described within 14 days of receipt. Please fill out
				return form provided.
			</li>
			<li>
				Our 3 month warranty is for graded goods and only covers the cost of the repair labour and not the parts.
			</li>
			<li>
				The IMEI must match what we have recorded when we dispatched the handset to you.
			</li>
			<li>
				Any attempt at repair will void your warranty / rights to return. This includes stock that was sold to yourself
				as faulty.
			</li>
			<li>
				The phone must be returned in the original condition that it was sent to you otherwise this voids your
				warranty / rights to return.
			</li>
		</ul>

		{{--<p>
			Please <a href="https://www.dropbox.com/s/ylio01fuama8ihf/TRG%20RMA%20Form.xls?dl=1" target="_blank">click here</a>
			to download the Recommercetech Returns Form.
		</p>--}}

		<p>
			Before returning your device please check your invoice to confirm which type of grade your purchased and whether
			it meets the return criteria.
		</p>

		<h2>Graded Stock - Fully working devices</h2>

		<h4>Accepted Faults</h4>

		<ul>
			<li>Screen faults (cracked or more than 2 dead pixels)</li>
			<li>Earpiece and microphone faults</li>
			<li>Charging port faults</li>
			<li>Home button faults</li>
			<li>WiFi faults</li>
			<li>Speakers</li>
			<li>Cameras</li>
			<li>Blocked</li>
			<li>Locked (if sold Unlocked)</li>
		</ul>

		<h4>Not Accepted Faults</h4>

		<ul>
			<li>Headphone jack faults</li>
			<li>Slow iOS</li>
			<li>Button faults where the button will work 75%+ of the time</li>
			<li>Battery life below original manufacturers specification. Unless impairs power up and normal functionality.</li>
			<li>Anything returned pass coded or iCloud locked.</li>
		</ul>

		<h2>Minor Faults - guaranteed small repair devices</h2>

		<h4>Accepted Faults</h4>

		<ul>
			<li>iCloud locked devices</li>
			<li>Blacklisted devices</li>
			<li>Water Damage</li>
			<li>Boot loop</li>
			<li>Not Activating</li>
			<li>Other motherboard faults</li>
			<li>IC Chip faults</li>
			<li>Touch ID faults</li>
			<li>Blocked</li>
			<li>Locked (if sold Unlocked)</li>
		</ul>

		<h4>Not Accepted Faults</h4>

		<ul>
			<li>Screen faults</li>
			<li>Battery faults</li>
			<li>Button faults</li>
			<li>Charging port faults</li>
			<li>Speaker faults</li>
			<li>Camera faults</li>
			<li>Earpiece faults</li>
			<li>Microphone faults</li>
		</ul>

		<h2>Mixed Faults - Sold as is with limited returns</h2>

		<h4>Accepted Faults</h4>

		<ul>
			<li>iCloud locked devices</li>
			<li>Blacklisted devices</li>
		</ul>

		<h4>Not Accepted Faults</h4>

		<ul>
			<li>Water Damage</li>
			<li>Motherboard faults</li>
			<li>IC chip faults</li>
			<li>No Power</li>
		</ul>

		<p>PLEASE NOTE: CONDITIONS OF SALE ALL PERSONAL DATA MUST BE REMOVED FROM EACH HANDSET PRIOR TO RESALE TO END USER</p>
	</div>

@endsection