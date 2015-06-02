<?php
$rwMember = $data->profile;
?>
<table>
    	<tr>
    		<td align="right">First name</td>
    		<td><?php echo $rwMember->sForename;?></td>
   		</tr>
    	<tr>
    		<td align="right">Last name</td>
    		<td><?php echo $rwMember->sSurname; ?></td>
   		</tr>
    	<tr>
    		<td  align="right">Address</td>
    		<td><?php echo $rwMember->sAddr1; ?></td>
   		</tr>
    	<tr>
    		<td align="right">&nbsp;</td>
    		<td><?php echo $rwMember->sAddr2; ?></td>
   		</tr>
    	<tr>
    		<td align="right">&nbsp;</td>
    		<td><?php echo $rwMember->sAddr3; ?></td>
   		</tr>
    	<tr>
    		<td  align="right">City/ Town </td>
    		<td><?php echo $rwMember->sTown; ?></td>
   		</tr>
    	<tr>
    		<td align="right">State/County</td>
    		<td><?php echo $rwMember->sCounty; ?></td>
   		</tr>
    	<tr>
    		<td align="right">Country</td>
    		<td><?php echo $rwMember->sCountry ?></td>
   		</tr>
    	<tr>
    		<td align="right">Zip/Postcode</td>
    		<td><?php echo $rwMember->sPostcode; ?></td>
   		</tr>
    	<tr>
    		<td align="right">Email</td>
    		<td><?php echo $rwMember->sEmail; ?></td>
   		</tr>
    	<tr>
    		<td align="right">Telephone</td>
    		<td><?php echo $rwMember->sTelephone; ?></td>
   		</tr>
    	<tr>
    		<td align="right">Mobile</td>
    		<td><?php echo $rwMember->sMobile; ?></td>
   		</tr>
</table>