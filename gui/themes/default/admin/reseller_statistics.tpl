			<script type="text/javascript">
				/*<![CDATA[*/
					$(document).ready(function () {
						$('.datatable').dataTable({"oLanguage": {DATATABLE_TRANSLATIONS}});
					});
				/*]]>*/
			</script>

			<!-- BDP: reseller_statistics_entries_block -->
			<table class="datatable" style="text-align: center;">
				<thead>
					<tr>
						<th>{TR_RESELLER_NAME}</th>
						<th>{TR_TRAFF}</th>
						<th>{TR_DISK}</th>
						<th>{TR_DOMAIN}</th>
						<th>{TR_SUBDOMAIN}</th>
						<th>{TR_ALIAS}</th>
						<th>{TR_MAIL}</th>
						<th>{TR_FTP}</th>
						<th>{TR_SQL_DB}</th>
						<th>{TR_SQL_USER}</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>{TR_RESELLER_NAME}</th>
						<th>{TR_TRAFF}</th>
						<th>{TR_DISK}</th>
						<th>{TR_DOMAIN}</th>
						<th>{TR_SUBDOMAIN}</th>
						<th>{TR_ALIAS}</th>
						<th>{TR_MAIL}</th>
						<th>{TR_FTP}</th>
						<th>{TR_SQL_DB}</th>
						<th>{TR_SQL_USER}</th>
					</tr>
				</tfoot>
				<tbody>
					<!-- BDP: reseller_statistics_entry_block -->
					<tr>
						<td>
							<a href="reseller_user_statistics.php?rid={RESELLER_ID}&amp;month={MONTH}&amp;year={YEAR}" title="{TR_RESELLER_TOOLTIP}" class="icon i_domain_icon">{RESELLER_NAME}</a>
						</td>
						<td>
							<div class="graph">
								<span style="width: {TRAFF_PERCENT}%"></span>
								<strong>{TRAFF_PERCENT} %</strong>
							</div>
							{TRAFF_MSG}
						</td>
						<td>
							<div class="graph">
								<span style="width: {DISK_PERCENT}%"></span>
								<strong>{DISK_PERCENT}%</strong>
							</div>
							{DISK_MSG}
						</td>
						<td>{DMN_MSG}</td>
						<td>{SUB_MSG}</td>
						<td>{ALS_MSG}</td>
						<td>{MAIL_MSG}</td>
						<td>{FTP_MSG}</td>
						<td>{SQL_DB_MSG}</td>
						<td>{SQL_USER_MSG}</td>
					</tr>
					<!-- EDP: reseller_statistics_entry_block -->
				</tbody>
			</table>
			<!-- EDP: reseller_statistics_entries_block -->
