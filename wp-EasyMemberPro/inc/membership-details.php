<?php
// Membership Levels Page

//var_dump($data->activeLevelData);die();
$levelData = $data->activeLevelData;

$language['membershipdetails_activelist'] = "Active Memberships";
$language['membershipdetails_activelistnone'] = "No Active Memberships";
$language['membershipdetails_inactivelist'] = "Inactive Memberships";
$language['membershipdetails_inactivelistnone'] = "No Inactive Memberships";

$language['membershipdetails_typefree'] = "Free";
$language['membershipdetails_typepaid'] = "Paid";
$language['membershipdetails_typeadmin'] = "Admin Assigned";

$language['membershipdetails_scheduleonce'] = "One Time Payment";
$language['membershipdetails_statuspendcancel'] = "Pending Cancellation as of ";

$language['membershipdetails_cancelconfirm'] = "Are you sure you wish to cancel this membership? This action cannot be undone!";

$language['membershipdetails_canceldate'] = 'Date Cancelled:';
// Table
$language['membershipdetails_level'] = 'Membership Level';
$language['membershipdetails_expire'] = 'Expires';
$language['membershipdetails_type'] = 'Type';
$language['membershipdetails_amount'] = 'Amount';
$language['membershipdetails_schedule'] = 'Schedule';
$language['membershipdetails_txnum'] = 'Transaction Number';
$language['membershipdetails_actions'] = 'Actions';


?>
<script type="text/javascript">
function openWin($url){window.location = $url;}
</script>
<!-- Active Membership List -->
<h1><?php echo $language['membershipdetails_activelist'] ?></h1>
<table width="100%" class="gridtable">
   <tr>
   <td class="gridheader"><?php echo $language['membershipdetails_level'] ?></td>
   <td class="gridheader"><?php echo $language['membershipdetails_expire'] ?></td>
   <!--<td class="gridheader"><?php echo $language['membershipdetails_type'] ?></td>
   <td class="gridheader"><?php echo $language['membershipdetails_amount'] ?></td>
   <td class="gridheader"><?php echo $language['membershipdetails_schedule'] ?></td>
   <td class="gridheader"><?php echo $language['membershipdetails_txnum'] ?></td>
   <td class="gridheader"><?php echo $language['membershipdetails_actions'] ?></td>-->
   </tr>
   <?php
foreach($levelData as $k=>$details){
	if($details->nActive){ 
	$gotactive = true;
	// Display Sales Data
		
		
			
		
	// Lets Show Cancel Link
		?>
    
    
   <tr>
   <td class="gridrow2"><?php echo $details->sLevel ?></td>
   <td class="gridrow2"><?php echo date(get_option( 'date_format' ),strtotime($details->nDateExpires)); ?></td>
   <!--<td class="gridrow2"><?php echo $details->type ?></td>
   <td class="gridrow2"><?php echo $details->amount ?></td>
   <td class="gridrow2"><?php echo $details->schedule ?></td>
   <td class="gridrow2"><?php echo $details->transnumber?></td>
   <td class="gridrow2"></td> -->
   </tr>
   
    <?php
		
		
		
		//die();
		}
	
	 
}
	 if($gotactive !=true){
		 ?>
         <tr><td colspan="7" class="gridrow2"><?php echo $language['membershipdetails_activelistnone'] ?></td></tr>
         <?php
		 
		 }
	 ?>

     </table>
     
 