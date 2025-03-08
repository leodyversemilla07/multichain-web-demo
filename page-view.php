<?php

define('const_max_retrieve_items', 1000);

$labels = multichain_labels();

no_displayed_error_result($liststreams, multichain('liststreams', '*', true));
no_displayed_error_result($getinfo, multichain('getinfo'));

$subscribed = false;
$viewstream = null;

foreach ($liststreams as $stream) {
	if (@$_POST['subscribe_' . $stream['createtxid']])
		if (no_displayed_error_result($result, multichain('subscribe', $stream['createtxid']))) {
			output_success_text('Successfully subscribed to stream: ' . $stream['name']);
			$subscribed = true;
		}

	if (@$_GET['stream'] == $stream['createtxid'])
		$viewstream = $stream;
}

if ($subscribed) // reload streams list
	no_displayed_error_result($liststreams, multichain('liststreams', '*', true));

?>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
	<div class="md:col-span-1">
		<form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
			<?php
			for ($subscribed = 1; $subscribed >= 0; $subscribed--) {
				?>
				<div class="mb-6">
					<div class="flex items-center mb-3">
						<div class="w-2 h-6 bg-blockchain-primary rounded-sm mr-3"></div>
						<h3 class="text-lg font-medium text-blockchain-dark m-0">
							<?php echo $subscribed ? 'Subscribed streams' : 'Other streams' ?>
						</h3>
					</div>

					<div class="space-y-4">
						<?php
						$hasStreams = false;
						foreach ($liststreams as $stream) {
							if ($stream['subscribed'] == $subscribed) {
								$hasStreams = true;
								$isActive = @$_GET['stream'] == $stream['createtxid'];
								?>
								<div class="bg-white rounded-lg shadow-sm border <?php echo $isActive ? 'border-blockchain-primary' : 'border-gray-200' ?> overflow-hidden transition-all hover:shadow-md">
									<div class="p-4 <?php echo $isActive ? 'bg-blue-50' : '' ?>">
										<div class="flex justify-between items-center mb-3">
											<div class="font-medium text-blockchain-dark">
												<?php if ($subscribed): ?>
													<a href="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>&stream=<?php echo html($stream['createtxid']) ?>" 
													   class="text-blockchain-primary hover:text-blockchain-dark transition-colors">
														<?php echo html($stream['name']) ?>
													</a>
												<?php else: ?>
													<?php echo html($stream['name']) ?>
												<?php endif; ?>
											</div>
											<?php if (!$subscribed): ?>
												<?php 
												$parts = explode('-', $stream['streamref']);
												if (is_numeric($parts[0]))
													$suffix = ' (' . ($getinfo['blocks'] - $parts[0] + 1) . ' blocks)';
												else
													$suffix = '';
												?>
												<button type="submit" name="subscribe_<?php echo html($stream['createtxid']) ?>" 
														class="bg-blockchain-primary hover:bg-blockchain-dark text-white text-xs py-1 px-2 rounded transition-colors">
													Subscribe<?php echo $suffix ?>
												</button>
											<?php endif; ?>
										</div>
										
										<div class="flex items-center text-sm text-gray-500 mb-2">
											<i class="fas fa-user mr-2 text-xs"></i>
											<span>Created by:</span>
										</div>
										<div class="hash-value text-xs mb-2">
											<?php echo format_address_html($stream['creators'][0], false, $labels) ?>
										</div>
										
										<?php if ($subscribed): ?>
											<div class="grid grid-cols-2 gap-3 mt-4">
												<div class="flex flex-col p-2 bg-gray-50 rounded border border-gray-100">
													<span class="text-xs text-gray-500 mb-1">Items</span>
													<span class="font-mono text-sm font-medium"><?php echo number_format($stream['items']) ?></span>
												</div>
												<div class="flex flex-col p-2 bg-gray-50 rounded border border-gray-100">
													<span class="text-xs text-gray-500 mb-1">Publishers</span>
													<span class="font-mono text-sm font-medium"><?php echo number_format($stream['publishers']) ?></span>
												</div>
											</div>
										<?php endif; ?>
									</div>
								</div>
								<?php
							}
						}
						if (!$hasStreams) {
							echo '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center text-gray-500">';
							echo '<i class="fas fa-info-circle mr-2"></i>';
							echo $subscribed ? 'No subscribed streams' : 'No other available streams';
							echo '</div>';
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
		</form>
	</div>

	<?php if (isset($viewstream)): ?>
		<div class="md:col-span-3">
			<?php
			if (isset($_GET['key'])) {
				$success = no_displayed_error_result($items, multichain('liststreamkeyitems', $viewstream['createtxid'], $_GET['key'], true, const_max_retrieve_items));
				$success = $success && no_displayed_error_result($keysinfo, multichain('liststreamkeys', $viewstream['createtxid'], $_GET['key']));
				$countitems = $keysinfo[0]['items'];
				$suffix = ' with key: <span class="font-mono bg-blue-50 px-1 py-0.5 rounded text-blockchain-primary">' . $_GET['key'] . '</span>';

			} elseif (isset($_GET['publisher'])) {
				$success = no_displayed_error_result($items, multichain('liststreampublisheritems', $viewstream['createtxid'], $_GET['publisher'], true, const_max_retrieve_items));
				$success = $success && no_displayed_error_result($publishersinfo, multichain('liststreampublishers', $viewstream['createtxid'], $_GET['publisher']));
				$countitems = $publishersinfo[0]['items'];
				$suffix = ' with publisher: <span class="font-mono bg-blue-50 px-1 py-0.5 rounded text-blockchain-primary">' . $_GET['publisher'] . '</span>';

			} else {
				$success = no_displayed_error_result($items, multichain('liststreamitems', $viewstream['createtxid'], true, const_max_retrieve_items));
				$countitems = $viewstream['items'];
				$suffix = '';
			}

			if ($success):
				$items = array_reverse($items); // show most recent first
				?>
				
				<div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden mb-6">
					<div class="p-5 bg-gradient-primary relative text-white">
						<div class="flex items-center mb-1">
							<i class="fas fa-stream mr-3"></i>
							<h3 class="text-xl font-medium m-0"><?php echo html($viewstream['name']) ?></h3>
						</div>
						<div class="flex items-center text-white/80 text-sm">
							<div class="flex items-center mr-4">
								<i class="fas fa-cube mr-1"></i>
								<span><?php echo count($items) ?> of <?php echo $countitems ?> <?php echo ($countitems == 1) ? 'item' : 'items' ?></span>
							</div>
							<?php if ($suffix): ?>
								<div class="flex items-center">
									<i class="fas fa-filter mr-1"></i>
									<span><?php echo $suffix ?></span>
								</div>
							<?php endif; ?>
						</div>
						
						<!-- Decorative pattern overlay -->
						<div class="absolute inset-0 opacity-10" 
							style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjAwIDEyMDAiIHN0eWxlPSJvcGFjaXR5OjAuMTU7ZmlsbDojZmZmZmZmIj48cGF0aCBkPSJNNjAwLDIwLDI1NiwzMzEuNSwyNTYsODY4LjUsNjAwLDExODAsOTQ0LDg2OC41LDk0NCwzMzEuNSw2MDAsMjBaTTYwMCw5NTcsNDIwLDg0NSw0MjAsNjIzLDYwMCw1MTEsNzgwLDYyMyw3ODAsODQ1LDYwMCw5NTdaIj48L3BhdGg+PC9zdmc+');">
						</div>
					</div>
					
					<?php if (count($items) > 0): ?>
						<div class="p-5">
							<div class="space-y-6">
								<?php foreach ($items as $item): ?>
									<?php
									$keys = isset($item['keys']) ? $item['keys'] : array($item['key']); // support MultiChain 2.0 or 1.0
									?>
									<div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow transition-all">
										<div class="grid grid-cols-1 md:grid-cols-3">
											<!-- Left side with meta information -->
											<div class="p-4 bg-gray-50 md:border-r border-gray-200">
												<div class="mb-4">
													<div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Publishers</div>
													<div class="flex flex-wrap gap-2">
														<?php foreach ($item['publishers'] as $publisher): ?>
															<?php 
															$link = './?chain=' . $_GET['chain'] . '&page=' . $_GET['page'] . '&stream=' . $viewstream['createtxid'] . '&publisher=' . $publisher;
															?>
															<a href="<?php echo $link ?>" class="inline-flex items-center px-2 py-1 bg-gray-200 hover:bg-gray-300 text-xs rounded transition-colors">
																<i class="fas fa-user-circle mr-1"></i>
																<?php echo format_address_html($publisher, false, $labels) ?>
															</a>
														<?php endforeach; ?>
													</div>
												</div>
												
												<div class="mb-4">
													<div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Key(s)</div>
													<div class="flex flex-wrap gap-2">
														<?php foreach ($keys as $key): ?>
															<?php 
															$link = './?chain=' . $_GET['chain'] . '&page=' . $_GET['page'] . '&stream=' . $viewstream['createtxid'] . '&key=' . $key;
															?>
															<a href="<?php echo $link ?>" class="inline-flex items-center px-2 py-1 bg-blue-100 hover:bg-blue-200 text-xs rounded transition-colors">
																<i class="fas fa-key mr-1"></i>
																<?php echo format_address_html($key, false, $labels) ?>
															</a>
														<?php endforeach; ?>
													</div>
												</div>
												
												<div>
													<div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Added</div>
													<div class="flex items-center">
														<i class="fas fa-clock text-blockchain-primary mr-2"></i>
														<span class="text-sm"><?php echo gmdate('Y-m-d H:i:s', isset($item['blocktime']) ? $item['blocktime'] : $item['time']) ?> GMT</span>
														<?php if (isset($item['blocktime'])): ?>
															<span class="ml-2 text-xs bg-green-100 text-green-800 py-0.5 px-1.5 rounded">confirmed</span>
														<?php endif; ?>
													</div>
												</div>
											</div>
											
											<!-- Right side with data content -->
											<div class="p-4 md:col-span-2">
												<?php
												if (isset($item['available']) && !$item['available']) {
													$showlabel = 'Data';
													$showhtml = '<div class="italic text-yellow-600 bg-yellow-50 p-3 rounded border border-yellow-200">
																	<i class="fas fa-exclamation-triangle mr-2"></i>
																	Not available. Either the data is off-chain and has not yet been delivered, or this item was rejected by a stream filter.
																</div>';

												} elseif (is_array($item['data']) && array_key_exists('json', $item['data'])) { // MultiChain 2.0 JSON item
													$showlabel = 'JSON data';
													$showhtml = '<pre class="bg-gray-50 p-3 rounded border border-gray-200 overflow-x-auto text-sm">' . 
																html(json_encode($item['data']['json'], JSON_PRETTY_PRINT)) . 
																'</pre>';

												} elseif (is_array($item['data']) && array_key_exists('text', $item['data'])) { // MultiChain 2.0 text item
													$showlabel = 'Text data';
													$showhtml = '<div class="bg-gray-50 p-3 rounded border border-gray-200">' . html($item['data']['text']) . '</div>';

												} else { // binary item
													if (is_array($item['data'])) { // long binary data item
														if (no_displayed_error_result($txoutdata, multichain('gettxoutdata', $item['data']['txid'], $item['data']['vout'], 1024))) // get prefix only for file name
															$binary = pack('H*', $txoutdata);
														else
															$binary = '';

														$size = $item['data']['size'];

													} else {
														$binary = pack('H*', $item['data']);
														$size = strlen($binary);
													}

													$file = txout_bin_to_file($binary); // see if file embedded as binary
							
													if (is_array($file)) {
														$showlabel = 'File';
														$filesize = number_format(ceil($size / 1024));
														$filename = strlen($file['filename']) ? html($file['filename']) : 'Download';
														
														$showhtml = '<div class="bg-gray-50 p-4 rounded border border-gray-200 flex items-center">
																		<div class="mr-4 flex-shrink-0">
																			<div class="w-12 h-12 rounded-lg bg-blockchain-primary/10 flex items-center justify-center">
																				<i class="fas fa-file-alt text-blockchain-primary text-xl"></i>
																			</div>
																		</div>
																		<div>
																			<div class="font-medium mb-1">' . $filename . '</div>
																			<div class="flex items-center text-sm">
																				<span class="text-gray-500 mr-3">' . $filesize . ' KB</span>
																				<a href="./download-file.php?chain=' . html($_GET['chain']) . '&txid=' . html($item['txid']) . '&vout=' . html($item['vout']) . '"
																				   class="inline-flex items-center text-blockchain-primary hover:text-blockchain-dark transition-colors">
																					<i class="fas fa-download mr-1"></i> Download
																				</a>
																			</div>
																		</div>
																	</div>';
													
													} else {
														$showlabel = 'Data';
														$showhtml = '<div class="bg-gray-50 p-3 rounded border border-gray-200 overflow-x-auto text-sm font-mono">' . html($binary) . '</div>';
													}
												}
												?>
												
												<div class="mb-2 flex items-center">
													<div class="text-xs uppercase tracking-wider text-gray-500 mr-2">
														<?php echo $showlabel ?>
													</div>
													<?php if ($showlabel == 'JSON data'): ?>
														<span class="text-xs bg-blue-100 py-0.5 px-1.5 rounded text-blue-800">JSON</span>
													<?php elseif ($showlabel == 'File'): ?>
														<span class="text-xs bg-green-100 py-0.5 px-1.5 rounded text-green-800">FILE</span>
													<?php elseif ($showlabel == 'Text data'): ?>
														<span class="text-xs bg-purple-100 py-0.5 px-1.5 rounded text-purple-800">TEXT</span>
													<?php else: ?>
														<span class="text-xs bg-gray-100 py-0.5 px-1.5 rounded text-gray-800">BINARY</span>
													<?php endif; ?>
												</div>
												
												<?php echo $showhtml ?>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php else: ?>
						<div class="p-10 text-center">
							<div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
								<i class="fas fa-folder-open text-xl"></i>
							</div>
							<p class="text-gray-500 text-lg m-0">No items in stream</p>
							<p class="text-sm text-gray-400 mt-2">This stream doesn't contain any data items yet.</p>
						</div>
					<?php endif; ?>
				</div>
				
				<?php if (count($items) == const_max_retrieve_items): ?>
					<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start">
						<div class="text-yellow-600 mr-3">
							<i class="fas fa-exclamation-triangle text-xl"></i>
						</div>
						<div>
							<h4 class="text-yellow-800 font-medium m-0 mb-1">Display limit reached</h4>
							<p class="text-yellow-700 text-sm m-0">
								Only showing the first <?php echo const_max_retrieve_items ?> items. Use stream filters to narrow down results.
							</p>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	<?php else: ?>
		<div class="md:col-span-3">
			<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8 text-center">
				<div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 text-blockchain-primary mb-4">
					<i class="fas fa-stream text-xl"></i>
				</div>
				<h3 class="text-xl font-medium mb-2">No Stream Selected</h3>
				<p class="text-gray-500 max-w-md mx-auto mb-6">
					Select a stream from the left panel to view its contents and explore the blockchain data.
				</p>
				<div class="inline-flex items-center text-sm bg-gray-100 py-2 px-3 rounded">
					<i class="fas fa-info-circle text-blockchain-primary mr-2"></i>
					<span>You can subscribe to streams to access their data</span>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>