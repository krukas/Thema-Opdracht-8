<div class="right" id="main-content-right">
	<div class="left" id="main-content-content">
		<?PHP 
		if(isset($_SESSION['stap4']) && $_SESSION['stap4']){
			$_SESSION['stap5'] = true;
			if(isset($_GET['attr']))
				$_SESSION['attr_id'] = $_GET['attr'];
		}

		if(!isset($_SESSION['user_id'])){
			redirect('login');
		}

		$vlucht = null;
		$room = null;
		$car = null;
		$attr = null;

		if(isset($_SESSION['stap5']) && $_SESSION['stap5']) {
			$client	= new SoapClient("http://env-9681936.jelastichosting.nl/fs/services/flightservice?wsdl");

			$req 						= new stdClass();
			$req->arg0 		= $_SESSION['vertrekhaven'];
			$req->arg1 		= $_SESSION['bestemming'];
			$req->arg2		= date("d-m-y H:i:s", strtotime($_SESSION['vanDatum']));
			$req->arg3		= date("d-m-y H:i:s", strtotime($_SESSION['totDatum']));

			try {
				$result	= $client->searchFlight($req);
				if(is_array($result->return)) {
					foreach ($result->return as $v) {
						if($v->flightId == $_SESSION['vlucht_id'])
							$vlucht = $v; 
					}
				} else {
					if($result->return->flightId == $_SESSION['vlucht_id'])
							$vlucht = $result->return; 
				}
			} catch(Exception $e) {
				//echo '<div class="errormessage" id="notification">'.$e->detail->fault->message.'</div>';
				echo $e;
			}

			//get hotel/room
			$client	= new SoapClient("http://tomcat.dkmedia.nl/hotelservice/hotelservice?wsdl");
			$req 					= new stdClass();
			$req->hotelId 			= $_SESSION['hotel_id'];
			$req->numberOfPersons 	= $_SESSION['aantalPersonen'];
			$req->fromDate 			= $_SESSION['vanDatum'];
			$req->toDate 			= $_SESSION['totDatum'];

			try {
				$result	= $client->ShowAvailableRooms($req);
				if(is_array($result->rooms->room)) {
					foreach ($result->rooms->room as $r) {
						if($r->id == $_SESSION['hotel_roomType'])
							$room = $r;
					}
				} else {
					if($result->rooms->room->id == $_SESSION['hotel_roomType'])
							$room = $result->rooms->room;
				}
			} catch(Exception $e) {
				//echo '<div class="errormessage" id="notification">'.$e->detail->fault->message.'</div>';
				echo $e;
			}

			function calculatePrice($room) {
				$datetime1 = new DateTime($_SESSION['vanDatum']);
				$datetime2 = new DateTime($_SESSION['totDatum']);
				$days = $datetime1->diff($datetime2);
				return ($room->pricePerNight * ceil( (double)$_SESSION['aantalPersonen'] / (double)$room->numberOfPersons ) ) * $days->days;
			}

			//get car
			$client	= new SoapClient("http://iis.dkmedia.nl/TO8.VervoerService.svc?wsdl");
			$req 					= new stdClass();
			$req->numP 	= $_SESSION['aantalPersonen'];

			try {
				$result	= $client->GetAvailableCars($req);
				if(isset($result->GetAvailableCarsResult->Car)) {
					if(is_array($result->GetAvailableCarsResult->Car)) {
						foreach ($result->GetAvailableCarsResult->Car as $value) {
							if($value->carId == $_SESSION['auto_id'])
								$car = $value;
						}
					} else {
						if($result->GetAvailableCarsResult->Car == $_SESSION['auto_id'])
							$car = $result->GetAvailableCarsResult->Car;
					}
				}
			} catch(Exception $e) {
				//echo '<div class="errormessage" id="notification">'.$e->detail->fault->message.'</div>';
				echo $e;
			}

			//get attractie
			class searchattractions{
				var $holiday_end_dateField;
				var $holiday_start_dateField;
				var $placeField;
				var $reachField;
			}
			class searchattraction {
				var $searchattractions;
				function __construct() {
					$this->searchattractions = new searchattractions();
				}
			}
			$client	= new SoapClient("http://iis.dkmedia.nl:85/WcfServiceLibrary1.Service1.svc?wsdl");
			$req = new searchattraction();
			$req->searchattractions->holiday_end_dateField		= $_SESSION['totDatum'];
			$req->searchattractions->holiday_start_dateField		= $_SESSION['vanDatum'];
			$req->searchattractions->placeField	= $_SESSION['bestemming'];
			$req->searchattractions->reachField	= 50;
			try {
				$result	= $client->searchAttraction($req);
				if(isset($result->searchAttractionResult->attraction)) {
					if(is_array($result->searchAttractionResult->attraction)) {
						foreach ($result->searchAttractionResult->attraction as $value) {
							$attracties[] = $value;
							if($value->idField == $_SESSION['attr_id'] )
								$attr = $value;
						}
					} else {
						if($result->searchAttractionResult->attraction->idField == $_SESSION['attr_id'] )
								$attr = $result->searchAttractionResult->attraction;
					}
				}
			} catch(Exception $e) {
				//echo '<div class="errormessage" id="notification">'.$e->detail->fault->message.'</div>';
				echo $e;
			}


		} else {
			redirect("vakantie");
		}
		?>	
		<h1><strong>Boek </strong> overzicht (<?php echo $_SESSION['vanDatum']." - ".$_SESSION['totDatum'] ?>)</h1>

		<table id="tableStyle">00
			<tr><td><span>Vertrekhaven</span> <?php echo $vlucht->departureAirport->name ?></td><td><span>Bestemming</span> <?php echo $vlucht->arrivalAirport->name ?></td></tr>
			<tr><td><span>Vluchtcode</span> <?php echo $vlucht->flightCode ?></td><td><span>Vliegtuig</span> <?php echo $vlucht->airline ." - ". $vlucht->airplane->type ?></td></tr>
			<tr><td><span>Vertrekdatum</span> <?php echo $vlucht->departureDate ?></td><td><span>Aankomstdatum</span> <?php echo $vlucht->arrivalDate ?></td></tr>
			<tr><td><span>Prijs</span> &euro; <?php echo number_format(($_SESSION['aantalPersonen']*89), 2, ',', '.') ?></td></tr>
		</table>
		<br/>
		<table id="tableStyle">
			<tr><td><span>Hotel</span> <?php echo $_SESSION['hotel_name'] ?></td></tr>
			<tr><td><span>Woonplaats</span> <?php echo $_SESSION['hotel_city'] ?></td></tr>
			<tr><td><span>Type kamer</span> <?php echo $room->name ?></td></tr>
			<tr><td><span>Prijs</span> &euro; <?php echo number_format(calculatePrice($room), 2, ',', '.') ?></td></tr>
		</table>
		<br/><br/>
		<pre>
		<?PHP
		var_dump($vlucht);
		var_dump($room);
		var_dump($car);
		var_dump($attr);
		?>
		</pre>
	</div>
</div>
<div class="clear"></div>