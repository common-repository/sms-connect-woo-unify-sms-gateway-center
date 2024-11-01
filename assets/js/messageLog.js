	/* 
	 * WooSMSConnect Unify SMS Gateway Center
	 * SMS Gateway Center
	 */
	jQuery(document).ready(function () {
		var data = unifysgcsms_data.messages;
		if (Array.isArray(data) && data.length == 0) {
			jQuery('#dlrData').html('');
			jQuery('#dlrData').append("<tr><td colspan=\"12\" align=\"center\">No records found!</td></tr>");
		} else {
			if (data.length > 0) {
				var records = data;
				var totalRecords = records.length;
				var recPerPage = 30; // Adjust as needed

				// If there's only one record, display it without needing pagination
				if (totalRecords <= recPerPage) {
					displayData(records);
					console.log('1');
				} else {
					// Set up pagination
					var totalPages = Math.ceil(totalRecords / recPerPage);
					jQuery('#pagination').twbsPagination({
						totalPages: totalPages,
						visiblePages: 10,
						next: 'Next',
						prev: 'Prev',
						onPageClick: function (event, page) {
							var displayRecordsIndex = (page - 1) * recPerPage;
							var endRec = displayRecordsIndex + recPerPage;
							var displayRecords = records.slice(displayRecordsIndex, endRec);
							console.log(displayRecords);
							displayData(displayRecords);
						}
					});
				}
			} else {
				jQuery('#dlrData').html('');
				jQuery('#dlrData').append("<tr><td colspan=\"7\" align=\"center\">No records found!</td></tr>");
			}
		}
	});

	function displayData(displayRecords) {
		jQuery('#dlrData').html('');
		var formattedDate = '-';
		var formattedReadTime = '-';
		for (var i = 0; i < displayRecords.length; i++) {
			if (displayRecords[i].deliveryTime) {
				var date = new Date(displayRecords[i].deliveryTime);
				var formattedDate = date.toLocaleString();
			}
			if (displayRecords[i].readTime) {
				var readTime = new Date(displayRecords[i].readTime);
				var formattedReadTime = readTime.toLocaleString();
			}
			var tr = jQuery('<tr/>');
			tr.append("<td>" + displayRecords[i].senderName + "</td>");
			tr.append("<td>" + displayRecords[i].mobileNo + "</td>");
			tr.append("<td>" + displayRecords[i].text + "</td>");
			tr.append("<td>" + displayRecords[i].uuId + "</td>");
			tr.append("<td>" + displayRecords[i].channel + "</td>");
			tr.append("<td>" + displayRecords[i].cost + "</td>");
			tr.append("<td>" + displayRecords[i].msgType + "</td>");
			tr.append("<td>" + displayRecords[i].status + "</td>");
			tr.append("<td>" + displayRecords[i].cause + "</td>");
			tr.append("<td>" + displayRecords[i].amount + "</td>");
			tr.append("<td>" + formattedDate + "</td>");
			jQuery('#dlrData').append(tr);
		}
	}