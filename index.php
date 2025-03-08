<?php

require_once 'functions.php';

$config = read_config();
$chain = @$_GET['chain'];

if (strlen($chain))
	$name = @$config[$chain]['name'];
else
	$name = '';

set_multichain_chain(@$config[$chain]);

?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>MultiChain Demo<?php if (strlen($name))
		echo ' - ' . html($name); ?></title>

	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

	<!-- Load Tailwind CSS via CDN -->
	<script src="https://cdn.tailwindcss.com"></script>

	<!-- Google Fonts for blockchain-appropriate typography -->
	<link rel="stylesheet"
		href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300;400;500;700&family=Rubik:wght@300;400;500;700&display=swap">

	<script>
		// Configure Tailwind with extended theme
		tailwind.config = {
			theme: {
				extend: {
					colors: {
						'blockchain-dark': '#1a2236',
						'blockchain-medium': '#2b3652',
						'blockchain-light': '#3c4b74',
						'blockchain-primary': '#3c89da',
						'blockchain-secondary': '#5a99d7',
					},
					fontFamily: {
						'mono': ['Roboto Mono', 'monospace'],
						'sans': ['Rubik', 'sans-serif'],
					},
					boxShadow: {
						'blockchain': '0 2px 15px rgba(0, 0, 0, 0.1)',
						'card': '0 2px 10px rgba(0, 0, 0, 0.08)',
						'card-hover': '0 5px 15px rgba(0, 0, 0, 0.12)',
					},
					animation: {
						'pulse-green': 'pulse-green 1.5s infinite',
					},
					keyframes: {
						'pulse-green': {
							'0%': { boxShadow: '0 0 0 0 rgba(40, 167, 69, 0.4)' },
							'70%': { boxShadow: '0 0 0 10px rgba(40, 167, 69, 0)' },
							'100%': { boxShadow: '0 0 0 0 rgba(40, 167, 69, 0)' }
						},
					},
					backgroundImage: {
						'gradient-primary': 'linear-gradient(135deg, #156ebf 0%, #3c89da 100%)',
						'gradient-header': 'linear-gradient(135deg, #1a2236 0%, #2b3652 80%, #3c4b74 100%)',
						'gradient-button': 'linear-gradient(90deg, #3c89da 0%, #5a99d7 100%)',
					}
				}
			}
		}
	</script>

	<!-- Alpine.js for interactive elements -->
	<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

	<style type="text/tailwindcss">
		/* Custom utility classes */
		@layer utilities {
			.td-break-words {
				word-wrap: break-word;
			}

			.hash-value {
				@apply font-mono bg-gray-50 py-1 px-2 rounded border border-gray-200 text-sm break-all;
			}

			.blockchain-data {
				@apply font-mono tracking-wider;
			}

			.status-indicator {
				@apply w-2.5 h-2.5 rounded-full mr-2;
			}

			.status-indicator.active {
				@apply bg-green-500 animate-pulse-green;
			}

			.badge-chain {
				@apply bg-gradient-to-r from-blockchain-primary to-blockchain-secondary text-white font-mono font-normal py-1 px-3 rounded-full text-xs uppercase tracking-wider border border-white/20 shadow-md;
			}
		}

		/* Hide elements with x-cloak until Alpine initializes */
		[x-cloak] { 
			display: none !important; 
		}
	</style>
</head>

<body class="bg-gray-50 text-gray-900 leading-relaxed font-sans">
	<!-- Main Container -->
	<div class="min-h-screen flex flex-col">
		<!-- Enhanced Header with blockchain aesthetics -->
		<header class="relative py-0 overflow-hidden">
			<!-- Gradient background with pattern overlay -->
			<div class="absolute inset-0 bg-gradient-header z-0">
				<!-- Animated background particles for blockchain visual effect -->
				<div class="absolute inset-0 overflow-hidden opacity-10" id="particles-blockchain">
					<div class="absolute right-0 top-0 w-1/2 h-full" 
						style="background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjAwIDEyMDAiIHN0eWxlPSJvcGFjaXR5OjAuMTU7ZmlsbDojZmZmZmZmIj48cGF0aCBkPSJNNjAwLDIwLDI1NiwzMzEuNSwyNTYsODY4LjUsNjAwLDExODAsOTQ0LDg2OC41LDk0NCwzMzEuNSw2MDAsMjBaTTYwMCw5NTcsNDIwLDg0NSw0MjAsNjIzLDYwMCw1MTEsNzgwLDYyMyw3ODAsODQ1LDYwMCw5NTdaIj48L3BhdGg+PC9zdmc+') no-repeat right center">
					</div>
				</div>
				<!-- Bottom border accent with gradient -->
				<div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blockchain-primary to-blockchain-secondary"></div>
			</div>
			
			<div class="container mx-auto px-4 relative z-10">
				<div class="py-8">
					<div class="flex flex-col md:flex-row items-center justify-between">
						<!-- Logo & Title Area -->
						<div class="flex items-center mb-6 md:mb-0">
							<!-- Logo container with glow effect -->
							<div class="w-16 h-16 relative mr-4 flex-shrink-0">
								<div class="absolute inset-0 rounded-lg bg-blockchain-primary opacity-20 animate-pulse"></div>
								<div class="flex items-center justify-center w-full h-full bg-white/10 rounded-lg border border-white/20 backdrop-blur-sm relative z-10">
									<i class="fas fa-cubes text-blockchain-primary text-3xl"></i>
								</div>
								<!-- Decorative corner accents -->
								<div class="absolute top-0 left-0 w-2 h-2 border-t-2 border-l-2 border-blockchain-primary"></div>
								<div class="absolute top-0 right-0 w-2 h-2 border-t-2 border-r-2 border-blockchain-primary"></div>
								<div class="absolute bottom-0 left-0 w-2 h-2 border-b-2 border-l-2 border-blockchain-primary"></div>
								<div class="absolute bottom-0 right-0 w-2 h-2 border-b-2 border-r-2 border-blockchain-primary"></div>
							</div>
							
							<!-- Title and Subtitle -->
							<div>
								<h1 class="text-2xl md:text-3xl font-mono font-bold text-white tracking-wide flex items-center">
									<a href="./" class="text-white hover:text-blockchain-primary transition-colors no-underline">
										MultiChain<span class="text-blockchain-primary">Demo</span>
									</a>
								</h1>
								<div class="flex items-center mt-1">
									<div class="h-px w-12 bg-gradient-to-r from-blockchain-primary to-transparent mr-3"></div>
									<p class="font-mono text-sm text-white/80 tracking-wider m-0">Distributed Ledger Technology</p>
								</div>
							</div>
						</div>
						
						<!-- Status & Connection Area -->
						<div class="w-full md:w-auto">
							<?php if (strlen($chain)) { ?>
								<!-- Connected state -->
								<div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-lg p-4 shadow-lg">
									<div class="flex items-center justify-between mb-3">
										<div class="flex items-center">
											<div class="status-indicator active"></div>
											<span class="text-white font-medium">Connected to Network:</span>
										</div>
										<span class="badge-chain ml-3 flex-shrink-0"><?php echo html($name) ?></span>
									</div>
									
									<div class="grid grid-cols-2 gap-3">
										<?php
										$nodeIsOffline = false;
										$error_message = '';
										
										try {
											$info = @multichain_getinfo();
											$nodeIsOffline = !is_array($info);
										} catch (Exception $e) {
											$nodeIsOffline = true;
											$error_message = $e->getMessage();
										}
										
										if (!$nodeIsOffline) {
										?>
											<div class="flex items-center py-1 px-3 bg-white/5 rounded border border-white/10 text-white/90">
												<i class="fas fa-layer-group text-blockchain-primary mr-2"></i>
												<div>
													<div class="text-xs text-white/60">Blocks</div>
													<div class="font-mono font-medium"><?php echo isset($info['blocks']) ? number_format($info['blocks']) : 'N/A'; ?></div>
												</div>
											</div>
											
											<div class="flex items-center py-1 px-3 bg-white/5 rounded border border-white/10 text-white/90">
												<i class="fas fa-network-wired text-blockchain-primary mr-2"></i>
												<div>
													<div class="text-xs text-white/60">Peers</div>
													<div class="font-mono font-medium"><?php echo isset($info['connections']) ? number_format($info['connections']) : 'N/A'; ?></div>
												</div>
											</div>
										<?php
										} else {
										?>
											<div class="col-span-2 py-1 px-3 bg-red-900/20 rounded border border-red-500/30 text-red-300 flex items-center">
												<i class="fas fa-exclamation-triangle mr-2 animate-pulse"></i>
												<span>Node offline</span>
											</div>
										<?php
										}
										?>
									</div>
								</div>
							<?php } else { ?>
								<!-- Not connected state -->
								<div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-lg p-4 text-center shadow-lg">
									<div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 border border-white/10 mb-3">
										<i class="fas fa-server text-xl text-blockchain-primary"></i>
									</div>
									<h3 class="text-white text-lg m-0 mb-1">Select a Blockchain Node</h3>
									<p class="text-white/70 text-sm m-0">Connect to a node to start managing blockchain assets</p>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</header>

		<!-- Animated particles script - add after the header -->
		<script>
			document.addEventListener("DOMContentLoaded", function() {
				// Simple animation for blockchain particles effect
				const particlesContainer = document.getElementById('particles-blockchain');
				if (particlesContainer) {
					for (let i = 0; i < 15; i++) {
						createParticle(particlesContainer);
					}
				}
			});

			function createParticle(container) {
				const particle = document.createElement('div');
				
				// Random size between 4px and 8px
				const size = Math.floor(Math.random() * 5) + 4;
				
				// Random position
				const posX = Math.floor(Math.random() * 100);
				const posY = Math.floor(Math.random() * 100);
				
				// Random opacity
				const opacity = (Math.floor(Math.random() * 4) + 2) / 10;
				
				// Random animation duration
				const duration = (Math.floor(Math.random() * 10) + 15);
				
				// Apply styles
				particle.style.cssText = `
					position: absolute;
					width: ${size}px;
					height: ${size}px;
					background-color: white;
					border-radius: 50%;
					left: ${posX}%;
					top: ${posY}%;
					opacity: ${opacity};
					transform: translate(-50%, -50%);
					animation: float ${duration}s infinite ease-in-out;
				`;
				
				container.appendChild(particle);
			}
		</script>

		<style>
			@keyframes float {
				0%, 100% {
					transform: translate(-50%, -50%) translateY(0px);
					opacity: 0.2;
				}
				50% {
					transform: translate(-50%, -50%) translateY(-15px);
					opacity: 0.4;
				}
			}
		</style>

		<?php if (strlen($chain)) { ?>
			<!-- Navigation with blockchain theme -->
			<nav class="bg-white shadow-md" x-data="{ open: false }">
				<div class="container mx-auto px-4">
					<div class="flex flex-wrap items-center">
						<button @click="open = !open" type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-blockchain-primary focus:outline-none mt-2 mb-2">
							<svg class="h-6 w-6" :class="{'hidden': open, 'inline-flex': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
							</svg>
							<svg class="h-6 w-6" :class="{'inline-flex': open, 'hidden': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
							</svg>
						</button>

						<div :class="{'hidden': !open}" class="w-full md:flex md:w-auto md:items-center">
							<ul class="flex flex-col md:flex-row md:space-x-1">
								<li>
									<a class="block py-4 px-5 font-medium border-b-3 border-transparent hover:bg-gray-50 hover:text-blockchain-primary hover:border-blockchain-primary transition-colors <?php echo (!isset($_GET['page'])) ? 'text-blockchain-primary border-blockchain-primary' : 'text-gray-700' ?>"
										href="./?chain=<?php echo html($chain) ?>">
										<i class="fas fa-tachometer-alt mr-2"></i> Dashboard
									</a>
								</li>
								<li>
									<a class="block py-4 px-5 font-medium border-b-3 border-transparent hover:bg-gray-50 hover:text-blockchain-primary hover:border-blockchain-primary transition-colors <?php echo (@$_GET['page'] == 'permissions') ? 'text-blockchain-primary border-blockchain-primary' : 'text-gray-700' ?>"
										href="./?chain=<?php echo html($chain) ?>&page=permissions">
										<i class="fas fa-key mr-2"></i> Permissions
									</a>
								</li>

								<!-- Assets Dropdown -->
								<li x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
									<a class="block py-4 px-5 font-medium border-b-3 border-transparent hover:bg-gray-50 hover:text-blockchain-primary hover:border-blockchain-primary transition-colors <?php echo (in_array(@$_GET['page'], ['issue', 'update', 'send'])) ? 'text-blockchain-primary border-blockchain-primary' : 'text-gray-700' ?>"
										href="#" @click.prevent>
										<i class="fas fa-coins mr-2"></i> Assets <i class="fas fa-chevron-down ml-1 text-xs"></i>
									</a>
									<div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-10 w-48 py-2 mt-1 bg-white rounded-md shadow-lg">
										<a href="./?chain=<?php echo html($chain) ?>&page=issue" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Issue Asset</a>
										<a href="./?chain=<?php echo html($chain) ?>&page=update" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Update Asset</a>
										<a href="./?chain=<?php echo html($chain) ?>&page=send" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Send Asset</a>
									</div>
								</li>

								<!-- Offers Dropdown -->
								<li x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
									<a class="block py-4 px-5 font-medium border-b-3 border-transparent hover:bg-gray-50 hover:text-blockchain-primary hover:border-blockchain-primary transition-colors <?php echo (in_array(@$_GET['page'], ['offer', 'accept'])) ? 'text-blockchain-primary border-blockchain-primary' : 'text-gray-700' ?>"
										href="#" @click.prevent>
										<i class="fas fa-handshake mr-2"></i> Offers <i class="fas fa-chevron-down ml-1 text-xs"></i>
									</a>
									<div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-10 w-48 py-2 mt-1 bg-white rounded-md shadow-lg">
										<a href="./?chain=<?php echo html($chain) ?>&page=offer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create Offer</a>
										<a href="./?chain=<?php echo html($chain) ?>&page=accept" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Accept Offer</a>
									</div>
								</li>

								<!-- Streams Dropdown -->
								<li x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
									<a class="block py-4 px-5 font-medium border-b-3 border-transparent hover:bg-gray-50 hover:text-blockchain-primary hover:border-blockchain-primary transition-colors <?php echo (in_array(@$_GET['page'], ['create', 'publish', 'view'])) ? 'text-blockchain-primary border-blockchain-primary' : 'text-gray-700' ?>"
										href="#" @click.prevent>
										<i class="fas fa-stream mr-2"></i> Streams <i class="fas fa-chevron-down ml-1 text-xs"></i>
									</a>
									<div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-10 w-48 py-2 mt-1 bg-white rounded-md shadow-lg">
										<a href="./?chain=<?php echo html($chain) ?>&page=create" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create Stream</a>
										<a href="./?chain=<?php echo html($chain) ?>&page=publish" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Publish to Stream</a>
										<a href="./?chain=<?php echo html($chain) ?>&page=view" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Streams</a>
									</div>
								</li>

								<!-- Filters Dropdown (conditional) -->
								<?php if (multichain_has_smart_filters()) { ?>
									<li x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
										<a class="block py-4 px-5 font-medium border-b-3 border-transparent hover:bg-gray-50 hover:text-blockchain-primary hover:border-blockchain-primary transition-colors <?php echo (in_array(@$_GET['page'], ['txfilter', 'streamfilter'])) ? 'text-blockchain-primary border-blockchain-primary' : 'text-gray-700' ?>"
											href="#" @click.prevent>
											<i class="fas fa-filter mr-2"></i> Filters <i class="fas fa-chevron-down ml-1 text-xs"></i>
										</a>
										<div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-10 w-48 py-2 mt-1 bg-white rounded-md shadow-lg">
											<a href="./?chain=<?php echo html($chain) ?>&page=txfilter" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Transaction Filter</a>
											<a href="./?chain=<?php echo html($chain) ?>&page=streamfilter" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Stream Filter</a>
										</div>
									</li>
								<?php } ?>
							</ul>
						</div>
					</div>
				</div>
			</nav>

			<!-- Main Content -->
			<main class="flex-grow container mx-auto px-4 py-8">
				<div class="flex flex-wrap justify-between items-center mb-6">
					<h2 class="text-2xl font-mono font-medium m-0">
						<?php
						$pageTitle = 'Dashboard';
						switch (@$_GET['page']) {
							case 'permissions':
								$pageTitle = 'Manage Permissions';
								break;
							case 'issue':
								$pageTitle = 'Issue New Asset';
								break;
							case 'update':
								$pageTitle = 'Update Asset';
								break;
							case 'send':
								$pageTitle = 'Send Assets';
								break;
							case 'offer':
								$pageTitle = 'Create Exchange Offer';
								break;
							case 'accept':
								$pageTitle = 'Accept Exchange Offer';
								break;
							case 'create':
								$pageTitle = 'Create New Stream';
								break;
							case 'publish':
								$pageTitle = 'Publish to Stream';
								break;
							case 'view':
								$pageTitle = 'View Streams';
								break;
							case 'txfilter':
								$pageTitle = 'Transaction Filter';
								break;
							case 'streamfilter':
								$pageTitle = 'Stream Filter';
								break;
						}
						echo $pageTitle;
						?>
					</h2>
					<div>
						<span class="blockchain-data">
							<i class="fas fa-clock text-blockchain-primary mr-1"></i> Node: <?php echo html($name) ?>
						</span>
					</div>
				</div>

				<!-- Page Content -->
				<?php
				switch (@$_GET['page']) {
					case 'label':
					case 'permissions':
					case 'issue':
					case 'update':
					case 'send':
					case 'offer':
					case 'accept':
					case 'create':
					case 'publish':
					case 'view':
					case 'txfilter':
					case 'streamfilter':
					case 'approve':
					case 'asset-file':
						require_once 'page-' . $_GET['page'] . '.php';
						break;

					default:
						require_once 'page-default.php';
						break;
				}
				?>
			</main>

		<?php } else { ?>
	<main class="flex-grow container mx-auto px-4 py-12">
		<div class="max-w-5xl mx-auto">
			<!-- Enhanced Hero introduction section -->
			<div class="mb-10 text-center relative">
				<!-- Decorative blockchain elements -->
				<div class="absolute -top-10 left-1/2 transform -translate-x-1/2 w-20 h-20 opacity-10">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor" class="text-blockchain-primary">
						<path d="M256,20,61.83,111.47V400.53L256,492,450.17,400.53V111.47Zm0,39.93,145.1,68.23L256,197.1,110.9,128.63ZM89.83,376.07V135.93L244,201.73V440.27Zm155.34,64.2V265.37l144.33-67.37V375.17Z"/>
					</svg>
				</div>
			
				<h2 class="text-3xl md:text-4xl font-mono font-bold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-blockchain-dark to-blockchain-primary">
					Welcome to <span class="text-blockchain-primary">MultiChain</span> Demo
				</h2>
				<p class="text-gray-600 max-w-3xl mx-auto text-lg leading-relaxed">
					Explore and manage blockchain networks with an intuitive interface. 
					Connect to a node below to begin your distributed ledger experience.
				</p>
				
				<!-- Visual separator -->
				<div class="flex items-center justify-center my-6">
					<div class="h-px w-16 bg-gray-200"></div>
					<div class="mx-4 text-blockchain-primary">
						<i class="fas fa-cube"></i>
					</div>
					<div class="h-px w-16 bg-gray-200"></div>
				</div>
			</div>
			
			<!-- Enhanced Blockchain Networks Card -->
			<div class="bg-white rounded-xl shadow-card overflow-hidden mb-12 border border-gray-100 transform transition-all hover:shadow-card-hover"></div>
				<!-- Improved header with better visual hierarchy -->
				<div class="bg-gradient-primary relative py-8 px-6">
					<div class="flex items-center justify-between relative z-10">
						<h3 class="text-white text-xl font-mono font-bold m-0 flex items-center">
							<i class="fas fa-network-wired mr-3"></i>
							Available Blockchain Networks
						</h3>
					</div>
					
					<!-- Decorative pattern overlay -->
					<div class="absolute inset-0 opacity-10" 
						style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgdmlld0JveD0iMCAwIDYwIDYwIj48cGF0aCBkPSJNMzAgNUw1IDE3LjVWNDIuNUwzMCA1NUw1NSA0Mi41VjE3LjVMMzAgNVpNMzAgMjBMMTUgMjcuNVYxMi41TDMwIDIwWk0zNSAxMi41VjI3LjVMMjAgMzVMMzUgNDIuNVYyNy41TDUwIDIwTDM1IDEyLjVaIiBmaWxsPSJ3aGl0ZSIvPjwvc3ZnPg==');">
					</div>
				</div>
				
				<!-- Container for network links with better spacing and hierarchy -->
				<div class="p-6 space-y-4">
					<?php if(count(array_filter($config, function($rpc) { return isset($rpc['rpchost']); })) > 0): ?>
						<div class="space-y-3 mb-6">
							<?php foreach ($config as $chain => $rpc) {
								if (isset($rpc['rpchost'])) { ?>
									<a href="./?chain=<?php echo html($chain) ?>" class="flex justify-between items-center p-4 bg-white border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blockchain-primary transition-all duration-200 no-underline group">
										<div class="flex items-center">
											<div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 text-blockchain-primary mr-4 group-hover:animate-pulse">
												<i class="fas fa-cube text-lg"></i>
											</div>
											<div>
												<div class="font-mono text-base font-medium text-gray-800"><?php echo html($rpc['name']) ?></div>
												<div class="text-xs text-gray-500 mt-1">Active Blockchain Node</div>
											</div>
										</div>
										<div class="bg-gradient-button text-white py-2 px-4 rounded-full flex items-center text-sm font-medium transition-all duration-200 hover:shadow-lg group-hover:scale-105">
											<span>Connect</span>
											<i class="fas fa-arrow-right ml-2 transition-transform group-hover:translate-x-1"></i>
										</div>
									</a>
								<?php }
							} ?>
						</div>
					<?php else: ?>
						<div class="flex items-center justify-center p-8 bg-gray-50 rounded-lg border border-gray-200">
							<div class="text-center">
								<div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-500 mb-4">
									<i class="fas fa-exclamation-triangle text-xl"></i>
								</div>
								<h4 class="text-lg font-medium text-gray-800 mb-2">No Blockchain Nodes Available</h4>
								<p class="text-gray-600">Please check your configuration file and ensure nodes are properly set up.</p>
							</div>
						</div>
					<?php endif; ?>

					<!-- About section with improved styling -->
					<div class="bg-gray-50 rounded-lg p-6 border border-gray-200 mt-8">
						<div class="flex flex-col md:flex-row gap-6">
							<div class="md:w-1/4 flex justify-center">
								<div class="w-20 h-20 rounded-full bg-gradient-to-br from-blockchain-primary/20 to-blockchain-primary/10 flex items-center justify-center text-blockchain-primary flex-shrink-0 border border-blockchain-primary/20 shadow-inner">
									<i class="fas fa-info text-2xl"></i>
								</div>
							</div>
							<div class="md:w-3/4">
								<h5 class="text-gray-800 font-semibold text-xl mb-3 flex items-center">
									About MultiChain
									<span class="ml-2 text-xs bg-blockchain-primary text-white py-1 px-2 rounded-full">Enterprise DLT</span>
								</h5>
								<p class="text-gray-600 leading-relaxed">
									MultiChain is an enterprise blockchain platform that helps organizations build
									and deploy blockchain applications with speed and security. It provides a
									secure, scalable and customizable infrastructure for the creation and deployment
									of private blockchains.
								</p>
								<div class="mt-4 flex gap-3">
									<a href="https://www.multichain.com/" target="_blank" class="text-blockchain-primary hover:text-blockchain-dark flex items-center text-sm">
										<i class="fas fa-external-link-alt mr-1"></i> Learn More
									</a>
									<a href="https://www.multichain.com/developers/" target="_blank" class="text-blockchain-primary hover:text-blockchain-dark flex items-center text-sm">
										<i class="fas fa-book mr-1"></i> Documentation
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Enhanced Getting Started Card -->
			<div class="bg-white rounded-lg shadow-card overflow-hidden transform transition-all hover:shadow-card-hover">
				<div class="flex flex-col md:flex-row">
					<!-- Left side decorative panel -->
					<div class="md:w-1/4 bg-gradient-to-br from-blockchain-dark to-blockchain-primary p-6 text-white flex items-center justify-center">
						<div class="text-center">
							<div class="w-16 h-16 rounded-full bg-white/10 border border-white/20 flex items-center justify-center mx-auto mb-4">
								<i class="fas fa-rocket text-2xl"></i>
							</div>
							<h4 class="text-xl font-medium">Getting Started</h4>
							<div class="h-1 w-12 bg-white/30 mx-auto mt-3"></div>
						</div>
					</div>
					
					<!-- Right side content -->
					<div class="md:w-3/4 p-6">
						<p class="text-gray-600 mb-6">Once connected to a blockchain node, you will be able to:</p>
						
						<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
							<div class="flex items-start group">
								<div class="mr-4 p-3 rounded-full bg-blue-50 text-blockchain-primary group-hover:scale-110 transition-transform">
									<i class="fas fa-coins text-lg"></i>
								</div>
								<div>
									<h5 class="font-medium text-gray-800 mb-1">Manage Assets</h5>
									<p class="text-sm text-gray-600">Issue, send, and update digital assets on the blockchain</p>
								</div>
							</div>
							<div class="flex items-start group">
								<div class="mr-4 p-3 rounded-full bg-blue-50 text-blockchain-primary group-hover:scale-110 transition-transform">
									<i class="fas fa-stream text-lg"></i>
								</div>
								<div>
									<h5 class="font-medium text-gray-800 mb-1">Work with Streams</h5>
									<p class="text-sm text-gray-600">Create streams and publish data to blockchain</p>
								</div>
							</div>
							<div class="flex items-start group">
								<div class="mr-4 p-3 rounded-full bg-blue-50 text-blockchain-primary group-hover:scale-110 transition-transform">
									<i class="fas fa-key text-lg"></i>
								</div>
								<div>
									<h5 class="font-medium text-gray-800 mb-1">Set Permissions</h5>
									<p class="text-sm text-gray-600">Control access rights to blockchain resources</p>
								</div>
							</div>
							<div class="flex items-start group">
								<div class="mr-4 p-3 rounded-full bg-blue-50 text-blockchain-primary group-hover:scale-110 transition-transform">
									<i class="fas fa-exchange-alt text-lg"></i>
								</div>
								<div>
									<h5 class="font-medium text-gray-800 mb-1">Exchange Assets</h5>
									<p class="text-sm text-gray-600">Create and accept atomic exchange offers</p>
								</div>
							</div>
						</div>
						
						<!-- Feature CTAs -->
						<div class="mt-8 pt-6 border-t border-gray-100 text-center">
							<span class="text-sm text-gray-500">Ready to explore blockchain technology?</span>
							<div class="mt-2">
								<button class="inline-flex items-center justify-center px-6 py-2 bg-gradient-button text-white font-medium rounded-full hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>
									<i class="fas fa-plug mr-2"></i> Select a blockchain node to begin
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
		<?php } ?>

		<!-- Fixed Footer with blockchain theme -->
		<footer class="bg-gradient-header shadow-inner text-gray-300 mt-auto">
			<div class="container mx-auto px-4">
				<!-- Top footer section with main content -->
				<div class="py-10 grid grid-cols-1 md:grid-cols-12 gap-10">
					<!-- About MultiChain -->
					<div class="md:col-span-5">
						<div class="flex items-center mb-4">
							<div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center mr-3 border border-white/10">
								<i class="fas fa-cubes text-blockchain-primary text-xl"></i>
							</div>
							<h5 class="text-white text-lg font-mono m-0">MultiChain Demo</h5>
						</div>
						
						<p class="text-gray-400 leading-relaxed mb-4">
							An enterprise blockchain platform enabling organizations to build and deploy 
							distributed ledger applications with speed, security, and scalability.
						</p>
						
						<div class="flex flex-col space-y-2">
							<div class="flex items-center blockchain-data text-sm text-white/70">
								<div class="w-6 text-center"><i class="fas fa-code-branch"></i></div>
								<span class="ml-2">MultiChain 2.3.3</span>
							</div>
							<div class="flex items-center blockchain-data text-sm text-white/70">
								<div class="w-6 text-center"><i class="fas fa-shield-alt"></i></div>
								<span class="ml-2">Enterprise Blockchain</span>
							</div>
						</div>
					</div>
					
					<!-- Resources Links -->
					<div class="md:col-span-4">
						<h5 class="text-white text-lg font-medium mb-6 border-b border-white/10 pb-2">Resources</h5>
						<ul class="space-y-4">
							<li>
								<a href="https://www.multichain.com/developers/" target="_blank" 
								class="flex items-center text-gray-400 hover:text-blockchain-primary transition-all duration-200">
									<div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center mr-2 border border-white/5">
										<i class="fas fa-file-code"></i>
									</div>
									<span>Developer Documentation</span>
								</a>
							</li>
							<li>
								<a href="https://www.multichain.com/download/" target="_blank" 
								class="flex items-center text-gray-400 hover:text-blockchain-primary transition-all duration-200">
									<div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center mr-2 border border-white/5">
										<i class="fas fa-download"></i>
									</div>
									<span>Download MultiChain</span>
								</a>
							</li>
							<li>
								<a href="https://www.multichain.com/getting-started/" target="_blank" 
								class="flex items-center text-gray-400 hover:text-blockchain-primary transition-all duration-200">
									<div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center mr-2 border border-white/5">
										<i class="fas fa-book"></i>
									</div>
									<span>Getting Started Guide</span>
								</a>
							</li>
						</ul>
					</div>
					
					<!-- Connect and Newsletter -->
					<div class="md:col-span-3">
						<h5 class="text-white text-lg font-medium mb-6 border-b border-white/10 pb-2">Connect</h5>
						<div class="flex space-x-3 mb-6">
							<a href="https://www.multichain.com/" class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center border border-white/10 text-gray-400 hover:text-white hover:bg-blockchain-primary/30 transition-all duration-200" title="Website" target="_blank">
								<i class="fas fa-globe"></i>
							</a>
							<a href="https://github.com/MultiChain" class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center border border-white/10 text-gray-400 hover:text-white hover:bg-blockchain-primary/30 transition-all duration-200" title="GitHub" target="_blank">
								<i class="fab fa-github"></i>
							</a>
							<a href="https://twitter.com/MultiChainOrg" class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center border border-white/10 text-gray-400 hover:text-white hover:bg-blockchain-primary/30 transition-all duration-200" title="Twitter" target="_blank">
								<i class="fab fa-twitter"></i>
							</a>
							<a href="https://www.linkedin.com/company/multichain/" class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center border border-white/10 text-gray-400 hover:text-white hover:bg-blockchain-primary/30 transition-all duration-200" title="LinkedIn" target="_blank">
								<i class="fab fa-linkedin-in"></i>
							</a>
						</div>
						
						<div class="bg-white/5 p-4 rounded-lg border border-white/10">
							<p class="text-sm text-white/80 mb-0">
								<i class="fas fa-info-circle mr-2 text-blockchain-primary"></i> 
								Open source blockchain platform for enterprise-grade applications.
							</p>
						</div>
					</div>
				</div>
				
				<!-- Bottom footer with copyright -->
				<div class="py-4 border-t border-white/10 flex flex-col md:flex-row justify-between items-center">
					<div class="blockchain-data text-sm text-white/60 mb-3 md:mb-0">
						MultiChain Demo Interface | Distributed Ledger Technology
					</div>
					<div class="text-xs text-white/50 font-mono">
						<a href="https://www.multichain.com/terms-of-use/" class="text-white/50 hover:text-white/80 mr-4" target="_blank">Terms</a>
						<a href="https://www.multichain.com/privacy-policy/" class="text-white/50 hover:text-white/80 mr-4" target="_blank">Privacy</a>
						<span>&copy; <?php echo date('Y'); ?> MultiChain</span>
					</div>
				</div>
			</div>
			
			<!-- Blockchain pattern overlay in footer -->
			<div class="absolute inset-0 opacity-5 pointer-events-none overflow-hidden" 
				style="background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjAwIDEyMDAiIHN0eWxlPSJvcGFjaXR5OjAuMTU7ZmlsbDojZmZmZmZmIj48cGF0aCBkPSJNNjAwLDIwLDI1NiwzMzEuNSwyNTYsODY4LjUsNjAwLDExODAsOTQ0LDg2OC41LDk0NCwzMzEuNSw2MDAsMjBaTTYwMCw5NTcsNDIwLDg0NSw0MjAsNjIzLDYwMCw1MTEsNzgwLDYyMyw3ODAsODQ1LDYwMCw5NTdaIj48L3BhdGg+PC9zdmc+') repeat center center;">
			</div>
		</footer>
	</div>
</body>

</html>