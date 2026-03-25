<?php
$company = $company ?? get_company();
$flash = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $company ? e($company['name']).' | ' : ''; ?>Balaji Tex Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/reset.css">
  <style>
    /* Additional cross-browser fixes */
    body {
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }
    
    /* Fix for IE11 flexbox */
    .flex {
      display: -webkit-box;
      display: -ms-flexbox;
      display: flex;
      -webkit-box-orient: horizontal;
      -webkit-box-direction: normal;
      -ms-flex-direction: row;
      flex-direction: row;
    }
    
    .flex-col {
      display: -webkit-box;
      display: -ms-flexbox;
      display: flex;
      -webkit-box-orient: vertical;
      -webkit-box-direction: normal;
      -ms-flex-direction: column;
      flex-direction: column;
    }
    
    .grid {
      display: -ms-grid;
      display: grid;
    }
    
    /* Fix for older browsers */
    input[type="date"], input[type="text"], input[type="number"], select, textarea {
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      -webkit-box-sizing: border-box;
      -moz-box-sizing: border-box;
      box-sizing: border-box;
    }
    
    /* Ensure consistent table rendering */
    table {
      table-layout: fixed;
      -webkit-box-sizing: border-box;
      -moz-box-sizing: border-box;
      box-sizing: border-box;
    }
    
    /* Consistent button styling */
    button, .btn {
      -webkit-box-sizing: border-box;
      -moz-box-sizing: border-box;
      box-sizing: border-box;
      -webkit-transition: all 0.2s ease;
      -moz-transition: all 0.2s ease;
      -ms-transition: all 0.2s ease;
      -o-transition: all 0.2s ease;
      transition: all 0.2s ease;
    }
    
    /* Fix for hover states */
    .hover\:bg-gray-50:hover {
      background-color: #f9fafb !important;
    }
    
    .hover\:bg-green-700:hover {
      background-color: #15803d !important;
    }
    
    .hover\:bg-orange-700:hover {
      background-color: #c2410c !important;
    }
    
    .hover\:bg-red-700:hover {
      background-color: #b91c1c !important;
    }
    
    .hover\:text-black:hover {
      color: #000000 !important;
    }
    
    .hover\:text-gray-700:hover {
      color: #374151 !important;
    }
    
    /* Consistent spacing */
    .gap-3 {
      -webkit-box-gap: 0.75rem;
      -moz-box-gap: 0.75rem;
      -ms-grid-gap: 0.75rem;
      gap: 0.75rem;
    }
    
    .gap-4 {
      -webkit-box-gap: 1rem;
      -moz-box-gap: 1rem;
      -ms-grid-gap: 1rem;
      gap: 1rem;
    }
    
    /* Fix for rounded corners */
    .rounded {
      -webkit-border-radius: 0.25rem;
      -moz-border-radius: 0.25rem;
      border-radius: 0.25rem;
    }
    
    .rounded-lg {
      -webkit-border-radius: 0.5rem;
      -moz-border-radius: 0.5rem;
      border-radius: 0.5rem;
    }
    
    /* Shadow fixes */
    .shadow {
      -webkit-box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
      -moz-box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <header class="bg-white border-b">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
      <div class="text-xl font-semibold text-gray-800">Balaji Tex Management</div>
      <nav class="space-x-4 text-sm">
        <a class="text-gray-700 hover:text-black" href="index.php?page=companies">Companies</a>
        <?php if ($company): ?>
          <a class="text-gray-700 hover:text-black" href="index.php?page=dashboard">Dashboard</a>
          <a class="text-gray-700 hover:text-black" href="index.php?page=yarn_types">Yarn Types</a>
          <a class="text-gray-700 hover:text-black" href="index.php?page=stocks">Stocks</a>
          <a class="text-gray-700 hover:text-black" href="index.php?page=workers">Workers</a>
          <a class="text-gray-700 hover:text-black" href="index.php?page=work_logs">Work Logs</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <?php if ($company): ?>
  <div class="bg-indigo-600 text-white">
    <div class="max-w-6xl mx-auto px-4 py-2 text-sm">Company: <span class="font-medium"><?php echo e($company['name']); ?></span></div>
  </div>
  <?php endif; ?>

  <?php if ($flash): ?>
  <div class="max-w-6xl mx-auto px-4 mt-4">
    <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 text-sm"><?php echo e($flash); ?></div>
  </div>
  <?php endif; ?>

  <main class="max-w-6xl mx-auto p-4">
