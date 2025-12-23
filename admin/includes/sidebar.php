<aside class="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-logo">
            <img src="../uploads/config/<?php echo $config['logo'] ?? 'logo.png'; ?>" alt="Logo">
        </a>
    </div>
    
    <?php 
    // Get menu items based on user permission level
    $menu_items = get_menu_items();
    ?>
    
    <style>
        .sidebar-menu li a iconify-icon {
            font-size: 20px;
            transition: all 0.2s;
        }
        .sidebar-menu li a:hover iconify-icon {
            transform: scale(1.1);
        }
        .icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .sidebar-menu li a:hover .icon-circle {
            transform: scale(1.05);
        }
        .sidebar-menu li.active .icon-circle {
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
    </style>
    
    <ul class="sidebar-menu">
        <?php foreach ($menu_items as $section => $items): ?>
            <li class="menu-header"><?php echo htmlspecialchars($section); ?></li>
            <?php foreach ($items as $item): 
                $iconColor = $item['color'] ?? '#4a66f9';
                $bgColor = $iconColor . '22'; // Add transparency
            ?>
                <li>
                    <a href="<?php echo $item['href']; ?>">
                        <span class="icon-circle" style="background: <?php echo $bgColor; ?>;">
                            <iconify-icon icon="<?php echo $item['icon']; ?>" style="color: <?php echo $iconColor; ?>;"></iconify-icon>
                        </span>
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>
</aside>