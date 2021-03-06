<!DOCTYPE html>
<html>
<?php
//ini_set('display_errors', 'On');
error_reporting(E_ALL);
?>

<head>
<?php
require_once('config.php');
include 'common/menuHead.inc';

$commandOptions = "";
$commandsJSON = file_get_contents('http://localhost:32322/commands');
$data = json_decode($commandsJSON, true);
foreach ($data as $cmd) {
    $commandOptions .= "<option value='" . $cmd['name'] . "'>" . $cmd['name'] . "</option>";
}
?>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="css/jquery.timepicker.css">
<script type="text/javascript" src="js/jquery.timepicker.min.js"></script>
<script language="Javascript">
$(document).ready(function() {
$('.default-value').each(function() {
var default_value = this.value;
$(this).focus(function() {
if(this.value == default_value) {
this.value = '';
$(this).css('color', '#333');
}
});
$(this).blur(function() {
if(this.value == '') {
$(this).css('color', '#999');
this.value = default_value;
}
});
});

    $('img[src="images/redesign/help-icon.svg"]').tooltip();
});
</script>
<script>
function AddScheduleEntry(data = {}) {
    var newEntry = 0;
    if (!data.hasOwnProperty('enabled')) {
        newEntry = 1;
        data.enabled = 1;
        data.day = 7;
        data.playlist = '';
        data.startTime = '00:00:00';
        data.endTime = '24:00:00';
        data.repeat = 1;
        data.startDate = '' + MINYEAR + '-01-01';
        data.endDate = '' + MAXYEAR + '-12-31';
        data.stopType = 0;
        data.endTimeOffset = 0;
        data.startTimeOffset = 0;
        data.type = 'playlist';
        data.command = '';
        data.args = [];
    }

    if (!data.hasOwnProperty('type') && (data.playlist != '')) {
        data.type = 'playlist';
    }

    var row = AddTableRowFromTemplate('tblScheduleBody');

    if (data.enabled == 1)
        row.find('.schEnable').prop('checked', true);

    row.find('.schType').val(data.type);

    row.find('.schStartDate').val(data.startDate);
    row.find('.schStartDate').after(HolidaySelect(data.startDate, "holStartDate"));
    row.find('.schEndDate').val(data.endDate);
    row.find('.schEndDate').after(HolidaySelect(data.endDate, "holEndDate"));

    if (data.day > 0x10000) {
        if (data.day & 0x04000)
            row.find('.maskSunday').prop('checked', true);
        if (data.day & 0x02000)
            row.find('.maskMonday').prop('checked', true);
        if (data.day & 0x01000)
            row.find('.maskTuesday').prop('checked', true);
        if (data.day & 0x00800)
            row.find('.maskWednesday').prop('checked', true);
        if (data.day & 0x00400)
            row.find('.maskThursday').prop('checked', true);
        if (data.day & 0x00200)
            row.find('.maskFriday').prop('checked', true);
        if (data.day & 0x00100)
            row.find('.maskSaturday').prop('checked', true);

        row.find('.schDay').val(65536).trigger('change');
    } else {
        row.find('.schDay').val(data.day).trigger('change');
    }

    if ((data.playlist != '') || (data.type == 'playlist')) {
        // Playlist or .fseq Sequence
        row.find('.schOptionsCommand').hide();

        if (data.hasOwnProperty('sequence') && data.sequence == 1) {
            row.find('.schOptionsPlaylist').hide();
            row.find('.schOptionsSequence').show();
            row.find('.schSequence').val(data.playlist);
            row.find('.schType').val('sequence');

            row.find('.schSequence').tooltip({
                content: function() {
                    return $(this).val().replace(/.fseq$/,'');
                }
            });
        } else {
            row.find('.schOptionsSequence').hide();
            row.find('.schOptionsPlaylist').show();
            row.find('.schPlaylist').val(data.playlist);
            row.find('.schType').val('playlist');

            row.find('.schPlaylist').tooltip({
                content: function() {
                    return $(this).val();
                }
            });
        }

    } else {
        // FPP Command
        row.find('.schOptionsPlaylist').hide();
        row.find('.schOptionsSequence').hide();
        row.find('.schOptionsCommand').show();
        row.find('.schType').val('command');

        FillInCommandTemplate(row, data);
    }

    if (data.startTime == '25:00:00')
        row.find('.schStartTime').val('SunRise');
    else if (data.startTime == '26:00:00')
        row.find('.schStartTime').val('SunSet');
    else
        row.find('.schStartTime').val(data.startTime);
    row.find('.schStartTime').trigger('change');

    if (data.endTime == '25:00:00')
        row.find('.schEndTime').val('SunRise');
    else if (data.endTime == '26:00:00')
        row.find('.schEndTime').val('SunSet');
    else
        row.find('.schEndTime').val(data.endTime);
    row.find('.schEndTime').trigger('change');
    
    if (data.endTimeOffset != null) row.find('.schEndTimeOffset').val(data.endTimeOffset);
    if (data.startTimeOffset != null) row.find('.schStartTimeOffset').val(data.startTimeOffset);


    row.find('.schRepeat').val(data.repeat);
    row.find('.schStopType').val(data.stopType);

    if (newEntry) {
        SetScheduleInputNames();
    }

    SetupDatePicker(row);
}

function getSchedule()
{
    $('#tblScheduleBody').empty();

    $.get('api/schedule', function(data) {
        GetPlaylistArray(false);

        var options = "";
        for(j = 0; j < playListArray.length; j++)
        {
            options +=  "<option value=\"" + playListArray[j] + "\">" + playListArray[j] + "</option>";
        }
        $('.fppTableRowTemplate').find('.schPlaylist').html(options);

        GetSequenceArray();
        options = "";
        for(j = 0; j < sequenceArray.length; j++)
        {
            options +=  "<option value=\"" + sequenceArray[j] + ".fseq\">" + sequenceArray[j] + "</option>";
        }
        $('.fppTableRowTemplate').find('.schSequence').html(options);

        for (var i = 0; i < data.length; i++) {
            AddScheduleEntry(data[i]);
        }

        SetScheduleInputNames();
    }).fail(function() {
    });
}

function ReloadSchedule()
{
    reloadPage();
}


function ScheduleDaysSelectChanged(item)
{
    if ($(item).find('option:selected').text() == 'Day Mask')
        $(item).parent().parent().find('.dayMask').show();
    else
        $(item).parent().parent().find('.dayMask').hide();
}

function SetRowDateTimeFieldVisibility(row) {
    var startDate = row.find('.schStartDate').val().replace(/[-0-9]/g,'');
    if (startDate != '') {
        row.find('.schStartDate').hide();
        row.find('.holStartDate').show();
    }
    var endDate = row.find('.schEndDate').val().replace(/[-0-9]/g,'');
    if (endDate != '') {
        row.find('.schEndDate').hide();
        row.find('.holEndDate').show();
    }
    
    var re = new RegExp(/^[0-9][0-9]:[0-9][0-9]:[0-9][0-9]$/);
    var startTime = row.find('.schStartTime').val();
    if (startTime.match(re)) {
        row.find('.startOffset').hide();
    } else {
        row.find('.startOffset').show();
    }
    var endTime = row.find('.schEndTime').val();
    if (endTime.match(re)) {
        row.find('.endOffset').hide();
    } else {
        row.find('.endOffset').show();
    }
}

function SetScheduleInputNames() {
	$('#tblScheduleBody > tr').each(function() {
		SetRowDateTimeFieldVisibility($(this));
	});

	$('.time').timepicker({
		'timeFormat': 'H:i:s',
		'typeaheadHighlight': false,
		'show2400': true,
		'noneOption': [
				{
					'label': 'Dawn',
					'value': 'Dawn'
				},
				{
					'label': 'SunRise',
					'value': 'SunRise'
				},
				{
					'label': 'SunSet',
					'value': 'SunSet'
				},
				{
					'label': 'Dusk',
					'value': 'Dusk'
				}
			]
		});

    SetupDatePicker("#tblScheduleBody > tr > td > input.date");
}

function SetupDatePicker(item)
{
	$(item).datepicker({
		'changeMonth': true,
		'changeYear': true,
		'dateFormat': 'yy-mm-dd',
		'minDate': new Date(MINYEAR-1, 1 - 1, 1),
		'maxDate': new Date(MAXYEAR, 12 - 1, 31),
		'showButtonPanel': true,
		'selectOtherMonths': true,
		'showOtherMonths': true,
		'yearRange': "" + MINYEAR +":" + MAXYEAR,
		'autoclose': true,
		'beforeShow': function( input ) {
            setTimeout(function() {
                var buttonPane = $( input )
                    .datepicker( "widget" )
                    .find( ".ui-datepicker-buttonpane" );

                $( "<button>", {
                    text: "Select from Holidays",
                    click: function() {
                        $('.ui-datepicker').hide();
                        $(input).hide();
                        $(input).val('Christmas');
                        $(input).parent().find('.holidays').val('Christmas');
                        $(input).parent().find('.holidays').show();
                    }
                }).appendTo( buttonPane ).addClass("ui-datepicker-clear ui-state-default ui-priority-primary ui-corner-all");
            }, 1 );
        }
    });
}

function ScheduleEntryTypeChanged(item)
{
    var row = $(item).parent().parent();

    if ($(item).val() == 'playlist') {
        // Playlist
        row.find('.schOptionsCommand').hide();
        row.find('.schOptionsSequence').hide();
        row.find('.schOptionsPlaylist').show();
    } else if ($(item).val() == 'sequence') {
        // Sequence
        row.find('.schOptionsCommand').hide();
        row.find('.schOptionsPlaylist').hide();
        row.find('.schOptionsSequence').show();
    } else {
        // FPP Command
        row.find('.schOptionsPlaylist').hide();
        row.find('.schOptionsSequence').hide();
        row.find('.schOptionsCommand').show();

        if (row.find('.cmdTmplArgs').val() == '')
            row.find('.cmdTmplArgsTable').hide();

        var json = row.find('.cmdTmplJSON').text();
        if (json == '') {
            var data = {};
            data.command = '';
            data.args = [];
            data.multisyncCommand = false;
            data.multisyncHosts = '';
            FillInCommandTemplate(row, data);
        }
    }
}

function TimeChanged(item)
{
    var re = new RegExp(/^\d{1,2}:\d{2}:\d{2}$/);
    if ($(item).val().match(re)) {
        $(item).parent().find('.offset').hide();
    } else {
        $(item).parent().find('.offset').show();
    }
}

function CloneSelectedEntry() {
    $('#tblScheduleBody > tr.selectedEntry').each(function() {
        AddScheduleEntry(GetScheduleEntryRowData($(this)));
    });
    SetScheduleInputNames();
}

function HolidaySelected(item)
{
    if ($(item).val() == 'SpecifyDate') {
        $(item).hide();
        $(item).parent().find('.date').show();
    } else {
        $(item).parent().find('.date').val($(item).val());
        $(item).parent().find('.date').hide();
    }
}

function HolidaySelect(userKey, classToAdd)
{
    var result = "<select class='holidays " + classToAdd + "' onChange='HolidaySelected(this);' style='display: none;'>";
    result += "<option value='SpecifyDate'>Specify Date</option>";

    for (var i in settings['locale']['holidays']) {
        var holiday = settings['locale']['holidays'][i];

        result += "<option value='" + holiday['shortName'] + "'";

        if (holiday['shortName'] == userKey)
            result += " selected";

        result += ">" + holiday['name'] + "</option>";
    }

    result += "</select>";
    return result;
}

function GetScheduleEntryRowData(item) {
    var schType = $(item).find('.schType').val();
    e = {};
    e.enabled = $(item).find('.schEnable').is(':checked') ? 1 : 0;
    e.sequence = 0;

    if (schType == 'playlist') {
        e.playlist = $(item).find('.schPlaylist').val();
    } else if (schType == 'sequence') {
        e.playlist = $(item).find('.schSequence').val();
        e.sequence = 1;
    }

    e.day = parseInt($(item).find('.schDay').val());
    e.startTime = $(item).find('.schStartTime').val();
    e.startTimeOffset = parseInt($(item).find('.schStartTimeOffset').val());
    e.endTime = $(item).find('.schEndTime').val();
    e.endTimeOffset = parseInt($(item).find('.schEndTimeOffset').val());
    e.repeat = parseInt($(item).find('.schRepeat').val());
    e.startDate = $(item).find('.schStartDate').val();
    e.endDate = $(item).find('.schEndDate').val();
    e.stopType = parseInt($(item).find('.schStopType').val());

    if (schType == 'command') {
        var json = $(item).find('.cmdTmplJSON').text();

        if (json == '') {
            var cmd = {};
            cmd.command = command;
            cmd.args = [];
            json = JSON.stringify(cmd);
            $(row).find('.cmdTmplJSON').html(json);
        }

        var jdata = JSON.parse(json);
        e.playlist = '';
        e.command = $(item).find('.cmdTmplCommand').val();
        e.args = jdata.args;

        if (jdata.hasOwnProperty('multisyncCommand')) {
            e.multisyncCommand = jdata.multisyncCommand;
            if (e.multisyncCommand)
                e.multisyncHosts = jdata.multisyncHosts;
        }
    }

    if (e.day & 0x10000) {
        if ($(item).find('.maskSunday').is(':checked'))
            e.day |= 0x4000;
        if ($(item).find('.maskMonday').is(':checked'))
            e.day |= 0x2000;
        if ($(item).find('.maskTuesday').is(':checked'))
            e.day |= 0x1000;
        if ($(item).find('.maskWednesday').is(':checked'))
            e.day |= 0x0800;
        if ($(item).find('.maskThursday').is(':checked'))
            e.day |= 0x0400;
        if ($(item).find('.maskFriday').is(':checked'))
            e.day |= 0x0200;
        if ($(item).find('.maskSaturday').is(':checked'))
            e.day |= 0x0100;
    }

    return e;
}

function SaveSchedule() {
    var data = [];

    $('#tblScheduleBody > tr').each(function() {
        data.push(GetScheduleEntryRowData($(this)));
    });

    dataStr = JSON.stringify(data, null, 4);

    $.ajax({
        url: 'api/schedule',
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        data: dataStr,
        success: function (response) {
            $.jGrowl('Schedule saved',{themeState:'success'});
            $.ajax({
                url: 'api/schedule/reload',
                type: 'post',
                contentType: 'application/json',
                success: function(response) {
                    $.jGrowl('Schedule reloaded',{themeState:'success'});
                }
            });
        },
        error: function() {
            DialogError('Error saving', 'Error saving schedule');
        }
    });
}

$(function() {
    $('#tblScheduleBody').on('mousedown', 'tr', function(event,ui) {
        HandleTableRowMouseClick(event, $(this));

        if ($('#tblScheduleBody > tr.selectedEntry').length > 0) {
            EnableButtonClass('deleteSchButton');
            EnableButtonClass('cloneSchButton');
        } else {
            DisableButtonClass('deleteSchButton');
            DisableButtonClass('cloneSchButton');
        }
    });
});

</script>
<script>
$(document).ready(function(){
    if (window.innerWidth > 600) {
        $('#tblScheduleBody').sortable({
            start: function (event, ui) {
                start_pos = ui.item.index();
            },
            update: function(event, ui) {
                SetScheduleInputNames();
            },
            beforeStop: function (event, ui) {
                //undo the firefox fix.
                // Not sure what this is, but copied from playlists.php to here
                if (navigator.userAgent.toLowerCase().match(/firefox/) && ui.offset !== undefined) {
                    $(window).unbind('scroll.sortableplaylist');
                    ui.helper.css('margin-top', 0);
                }
            },
            helper: function (e, ui) {
                ui.children().each(function () {
                    $(this).width($(this).width());
                });
                return ui;
            },
            scroll: true
        }).disableSelection();
    }
});
</script>
<title><? echo $pageTitle; ?></title>
<style>
.clear {
	clear: both;
}
.items {
	width: 40%;
	background: rgb#FFF;
	float: right;
	margin: 0, auto;
}
.selectedEntry {
	background: #CCC;
}
.pl_title {
	font-size: larger;
}
h4, h3 {
	padding: 0;
	margin: 0;
}
.tblheader {
	background-color: #CCC;
	text-align: center;
}
.dayMaskTable {
	padding: 2px 0px 0px 0px;
}
tr.rowScheduleDetails td.dayMaskTD {
    padding: 2px 2px;
}
tr.rowScheduleDetails input.dayMaskCheckbox {
    margin: 0px 0px 0px 0px;
}
tr.rowScheduleDetails select.selPlaylist {
    width: 250px;
}
tr.rowScheduleDetails select.selPlaylist option {
    overflow: hidden;
    white-space: no-wrap;
    text-overflow: ellipsis;
}
.center {
	text-align: center;
}
</style>
</head>

<body onload="PopulateCommandListCache(); getSchedule();">
<div id="bodyWrapper">
  <?php	include 'menu.inc'; ?>
  <div class="container">
      <h1 class="title">Scheduler</h1>
      <div class="pageContent"> 
          <div>
            <div class="form-actions">
                <div class="td"><input class="buttons btn-success" type='button' value="Save" onClick='SaveSchedule();' /></div>
                <div class="td"><button class="buttons btn-outline-success" type="button"  onClick="AddScheduleEntry();"><i class="fas fa-plus"></i> Add</button></div>
                <div class="td"><input class="buttons disableButtons cloneSchButton" type="button" value="Clone" onClick="CloneSelectedEntry();"/></div>
                <div class="td"><input class="buttons disableButtons deleteSchButton" type="button" value="Delete" onClick="DeleteSelectedEntries('tblScheduleBody'); DisableButtonClass('deleteSchButton');"/></div>
                <div class="td"><input class="buttons" type="button" value="Clear Selection" onClick="$('#tblScheduleBody tr').removeClass('selectedEntry'); DisableButtonClass('deleteSchButton'); DisableButtonClass('cloneSchButton');"/></div>
                <div class="td"><input class="buttons" type="button" value = "Reload" onClick="ReloadSchedule();"/></div>
                <div class="td"><input type='button' class='buttons wideButton' onClick='PreviewSchedule();' value='View Schedule'></div>
            </div>
            <div class='fppTableWrapper'>
                <div class='fppTableContents'  role="region" aria-labelledby="tblSchedule" tabindex="0">
                    
                    <table class='fppTableRowTemplate template-tblScheduleBody'>
                        <tr class='rowScheduleDetails'>
                            <td class='center' ><input class='schEnable' type='checkbox' /></td>
                            <td><input class='date center schStartDate' type='text' size='9'  /></td>
                            <td><input class='date center schEndDate' type='text' size='9' /></td>
                            <td><select class='schDay' onChange='ScheduleDaysSelectChanged(this);'>
                                    <option value='7'>Everyday</option>
                                    <option value='0'>Sunday</option>
                                    <option value='1'>Monday</option>
                                    <option value='2'>Tuesday</option>
                                    <option value='3'>Wednesday</option>
                                    <option value='4'>Thursday</option>
                                    <option value='5'>Friday</option>
                                    <option value='6'>Saturday</option>
                                    <option value='8'>Mon-Fri</option>
                                    <option value='9'>Sat/Sun</option>
                                    <option value='10'>Mon/Wed/Fri</option>
                                    <option value='11'>Tues/Thurs</option>
                                    <option value='12'>Sun-Thurs</option>
                                    <option value='13'>Fri/Sat</option>
                                    <option value='14'>Odd</option>
                                    <option value='15'>Even</option>
                                    <option value='65536'>Day Mask</option>
                                    </select>
                                <br>
                                <span class='dayMask'>
                                    <table border=0 cellpadding=0 cellspacing=0>
                                        <tr><th>S</th>
                                            <th>M</th>
                                            <th>T</th>
                                            <th>W</th>
                                            <th>T</th>
                                            <th>F</th>
                                            <th>S</th>
                                            </tr>
                                        <tr><td><input class='maskSunday' type='checkbox' /></td>
                                            <td><input class='maskMonday' type='checkbox' /></td>
                                            <td><input class='maskTuesday' type='checkbox' /></td>
                                            <td><input class='maskWednesday' type='checkbox' /></td>
                                            <td><input class='maskThursday' type='checkbox' /></td>
                                            <td><input class='maskFriday' type='checkbox' /></td>
                                            <td><input class='maskSaturday' type='checkbox' /></td>
                                        </tr>
                                        <tr><td><input class='maskSunday' type='checkbox' /></td>
                                            <td><input class='maskMonday' type='checkbox' /></td>
                                            <td><input class='maskTuesday' type='checkbox' /></td>
                                            <td><input class='maskWednesday' type='checkbox' /></td>
                                            <td><input class='maskThursday' type='checkbox' /></td>
                                            <td><input class='maskFriday' type='checkbox' /></td>
                                            <td><input class='maskSaturday' type='checkbox' /></td>
                                        </tr>
                                    </table>
                                </span>
                            </td>
                            <td><input class='time center schStartTime' type='text' size='6' onChange='TimeChanged(this);' />
<span class='offset startOffset'><br>+<input class='center schStartTimeOffset' type='number' size='4' value='0' min='-120' max='120'>min</span></td>
                            <td><select class='schType' onChange='ScheduleEntryTypeChanged(this);'>
                                <option value='playlist'>Playlist</option>
                                <option value='sequence'>Sequence</option>
                                <option value='command'>Command</option>
                                 </select>
                            </td>
                        <!-- start Playlist and Sequence options -->
                             <td class='schOptionsPlaylist'>
                                 <select class='schPlaylist' title=''>
                                </select>
                            </td>
                             <td class='schOptionsSequence'>
                                 <select class='schSequence' title=''>
                                </select>
                            </td>
                            <td class='schOptionsPlaylist schOptionsSequence'><input class='time center schEndTime' type='text' size='6' onChange='TimeChanged(this);' />
                                <span class='offset endOffset'><br>+<input class='center schEndTimeOffset' type='number' size='4' value='0' min='-120' max='120'>min</span>
                            </td>
                            <td class='schOptionsPlaylist schOptionsSequence' class='center' >
                                <select class='schRepeat'>
                                    <option value='0'>None</option>
                                    <option value='1'>Immediate</option>
                                    <option value='500'>5 Min.</option>
                                    <option value='1000'>10 Min.</option>
                                    <option value='1500'>15 Min.</option>
                                    <option value='2000'>20 Min.</option>
                                    <option value='3000'>30 Min.</option>
                                    <option value='6000'>60 Min.</option>
                                </select>
                            </td>
                            <td class='schOptionsPlaylist schOptionsSequence' class='center' >
                                <select class='schStopType'>
                                    <option value='0'>Graceful</option>
                                    <option value='2'>Graceful Loop</option>
                                    <option value='1'>Hard Stop</option>
                                </select>
                            </td>
                            <!-- end 'Playlist' options -->
                            <!-- start 'FPP Command' options -->
                            <td class='schOptionsCommand' colspan='4'><select class='cmdTmplCommand' onChange='EditCommandTemplate($(this).parent().parent());'><? echo $commandOptions; ?></select>
                                <input type='button' class='buttons reallySmallButton' value='Edit' onClick='EditCommandTemplate($(this).parent().parent());'>
                                <input type='button' class='buttons smallButton' value='Run Now' onClick='RunCommandJSON($(this).parent().find(".cmdTmplJSON").text());'>
                                <img class='cmdTmplTooltipIcon' title='' src='images/redesign/help-icon.svg' width=22 height=22>
                                <span class='cmdTmplMulticastInfo'></span>
                                <table class='cmdTmplArgsTable'><tr><th class='left'>Args:</th><td><span class='cmdTmplArgs'></span></td></tr></table>
                                <span class='cmdTmplJSON' style='display: none;'></span>
                            </td>
                            <!-- end 'FPP Command' options -->
                        </tr>
                    </table>
                    <table id='tblSchedule' class="fppSelectableRowTable">

                        <thead id='tblScheduleHead'>
                            <tr>
                                <th title='Schedule enabled/disabled'>Active</th>
                                <th title='Start Date'>Start Date</th>
                                <th title='End Date'>End Date</th>
                                <th title='Day(s) of the week'>Day(s)</th>
                                <th title='Start Time'>Start<br>Time</th>
                                <th title='Schedule Type'>Schedule<br>Type</th>
                                <th title='Playlist'>Playlist /<br> Command Args</th>
                                <th title='End Time'>End Time</th>
                                <th title='Repeat playlist'>Repeat</th>
                                <th title='Playlist Stop Type'>Stop Type</th>
                            </tr>
                        </thead>
                        <tbody id='tblScheduleBody'>
                        </tbody>
                    </table>
                </div>
    	    </div>
    	<div>
     

            <div class="backdrop">
                <b>Notes</b>:
                <ul style="margin-top:0px;">
                    <li>If playlist times overlap, items higher in the list have priority.</li>
                    <li>Drag/Drop to change order</li>
                    <li>CTRL+Click to select multiple items</li>
                    <li>Odd/Even for Days is used to alternate playlist over 2 days <img style="vertical-align:middle" width=22 height=22 src="images/redesign/help-icon.svg" title="This is not based on the day of the week or month or year. It is odd/even starting at July 15, 2013, the day of the first commit to the FPP code repository. This was done so that it did not have two odd days in a row on the 7th and first days of the week or on months that have 31 days going into the next month, etc."></li>
                </ul>
            </div>

 



            </div>

      </div>
      
  </div>
  <?php	include 'common/footer.inc'; ?>
</div>
</body>
</html>
