// GCash Payment Modal logic
function showGcashModal() {
    var total = document.getElementById('total')?.textContent || '₱0.00';
    document.getElementById('gcashAmount').textContent = total;
    document.getElementById('gcashModal').classList.remove('hidden');
}

function closeGcashModal() {
    document.getElementById('gcashModal').classList.add('hidden');
}
// Get PHP signature for an item's price
function getSignedPrice(item, callback) {
    fetch('sign_items.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ price: item.price, id: item.id })
    })
    .then(res => res.json())
    .then(data => callback(data.signature))
    .catch(() => callback(''));
}
// Login system
    let currentPin = '';
    const correctPin = '1234';

    // Daily earnings tracking
    let dailyEarnings = {
        totalSales: 0,
        totalTax: 0,
        totalOrders: 0,
        totalItemsSold: 0,
        orders: [],
        itemsSold: {}
    };

    let cart = [];
    let currentCategory = 'all';
    let currentTab = 'pos';

    // PIN Functions
    function addPin(digit) {
        if (currentPin.length < 4) {
            currentPin += digit;
            updatePinDisplay();
        }
    }

    function clearPin() {
        currentPin = '';
        updatePinDisplay();
        hideError();
    }

    function updatePinDisplay() {
        for (let i = 1; i <= 4; i++) {
            const dot = document.getElementById(`dot${i}`);
            if (!dot) continue;
            if (i <= currentPin.length) {
                dot.classList.add('filled');
            } else {
                dot.classList.remove('filled');
            }
        }
    }

    function submitPin() {
        if (currentPin.length === 4) {
            if (currentPin === correctPin) {
                document.getElementById('loginScreen')?.classList.add('hidden');
                document.getElementById('posSystem')?.classList.remove('hidden');
                initializeApp();
            } else {
                showError();
                shakeLogin();
                clearPin();
            }
        }
    }

    function showError() {
        document.getElementById('errorMessage')?.classList.remove('hidden');
        setTimeout(hideError, 3000);
    }

    function hideError() {
        document.getElementById('errorMessage')?.classList.add('hidden');
    }

    function shakeLogin() {
        const loginContainer = document.querySelector('#loginScreen > div');
        if (!loginContainer) return;
        loginContainer.classList.add('shake');
        setTimeout(() => {
            loginContainer.classList.remove('shake');
        }, 500);
    }

    // Logout: clear storage and go to login.php
    function logout() {
        // Always redirect to login.php after clearing session/local storage
        if (confirm('Are you sure you want to logout?')) {
            try {
                sessionStorage.clear();
                localStorage.clear();
            } catch (e) { /* ignore */ }
        }
        window.location.href = 'login.php';
    }

    // Initialize the application
    function initializeApp() {
        updateInventoryTable();
        updateEarningsDisplay();
        updateCurrentDate();
    }

    function updateCurrentDate() {
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        const el = document.getElementById('currentDate');
        if (el) el.textContent = dateStr;
    }

    // Switch between tabs (uses data-tab attribute on your tab buttons if present)
    function switchTab(tab) {
        currentTab = tab;

        // Update tab buttons (look for .tab-btn[data-tab="..."])
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.querySelector(`.tab-btn[data-tab="${tab}"]`);
        if (activeBtn) activeBtn.classList.add('active');

        // Show/hide content
        document.getElementById('posTab')?.classList.add('hidden');
        document.getElementById('inventoryTab')?.classList.add('hidden');
        document.getElementById('earningsTab')?.classList.add('hidden');

        if (tab === 'pos') {
            document.getElementById('posTab')?.classList.remove('hidden');
        } else if (tab === 'inventory') {
            document.getElementById('inventoryTab')?.classList.remove('hidden');
            updateInventoryTable();
        } else if (tab === 'earnings') {
            document.getElementById('earningsTab')?.classList.remove('hidden');
            updateEarningsDisplay();
            updateOrderHistory();
            updateTopSellingItems();
        }
    }

    // Helper functions (add if missing)
function getStockStatus(item) {
    if (item.stock === 0) return 'out';
    if (item.stock <= item.min_stock) return 'low';
    if (item.stock <= item.min_stock * 2) return 'medium';
    return 'high';
}
function getStockClass(status) {
    if (status === 'out' || status === 'low') return 'stock-low';
    if (status === 'medium') return 'stock-medium';
    return 'stock-high';
}
function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, function(m){ if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }

function displayMenuItems(items) {
    const grid = document.getElementById('menuGrid');
    if (!grid) return;
    if (!items || items.length === 0) {
        grid.innerHTML = '<div class="col-span-full text-center py-12"><i class="fas fa-utensils text-6xl opacity-30"></i><p class="text-gray-400">No menu items available. Please add items in Inventory.</p></div>';
        return;
    }
    grid.innerHTML = items.map(item => {
        const status = getStockStatus(item);
        const statusClass = getStockClass(status);
        const disabled = item.stock === 0;
        return `<div class="menu-item-card rounded-xl p-4 relative ${disabled?'opacity-60':''}">
                    <div class="stock-badge ${statusClass}"><i class="fas fa-box"></i> ${item.stock}</div>
                    <h3 class="text-lg font-bold">${escapeHtml(item.name)}</h3>
                    <p class="text-red-600 text-sm bg-red-50 inline-block px-2 py-1 rounded-full my-2">${escapeHtml(item.category)}</p>
                    <p class="price-tag inline-block mt-1">₱${parseFloat(item.price).toFixed(2)}</p>
                    <p class="text-gray-600 text-sm my-2">${escapeHtml(item.description || '')}</p>
                    <button class="w-full btn-primary py-2 rounded-lg ${disabled?'opacity-50 cursor-not-allowed':''}" ${disabled?'disabled':`onclick="addToCart(${item.id})"`}>${disabled?'Out of Stock':'Add to Order'}</button>
                </div>`;
    }).join('');
}

    // Render menu items
    function displayMenuItems(items) {
        const menuGrid = document.getElementById('menuGrid');
        if (!menuGrid) return;
        menuGrid.innerHTML = '';
        items.forEach(item => {
            const status = getStockStatus(item);
            const stockClass = getStockClass(status);
            const isDisabled = item.stock === 0;
            getSignedPrice(item, function(signature) {
                const div = document.createElement('div');
                div.className = `menu-item bg-white rounded-xl p-6 shadow-md border border-gray-200 transition-all duration-200 cursor-pointer relative ${stockClass}`;
                div.setAttribute('data-category', item.category);
                div.innerHTML = `
                    <div class="stock-indicator stock-${status}">
                        ${item.stock} left
                    </div>
                    <div class="flex justify-between items-start mb-3 mt-6">
                        <h3 class="text-lg font-semibold text-gray-800">${item.name}</h3>
                        <span class="text-xl font-bold text-green-600">₱${item.price.toFixed(2)}</span>
                        <span class="text-xs text-gray-400 ml-2">${signature ? 'Signed: ' + signature : ''}</span>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">${item.description}</p>
                    <button class="w-full py-2 rounded-lg font-medium transition-colors duration-200 ${
                        isDisabled 
                            ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                            : 'bg-blue-600 text-white hover:bg-blue-700'
                    }" 
                    ${isDisabled ? 'disabled' : `onclick=\"addToCart(${item.id})\"`}>
                        ${isDisabled ? 'Out of Stock' : 'Add to Order'}
                    </button>
                `;
                menuGrid.appendChild(div);
            });
        });
    }

    // Filter by category (looks for .category-btn[data-category="..."] to set active class)
    function filterCategory(category) {
        currentCategory = category;
        document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
    const catBtn = document.querySelector(`.category-btn[data-category="${category}"]`);
        if (catBtn) catBtn.classList.add('active');

        const filteredItems = category === 'all' ? menuItems : menuItems.filter(item => item.category === category);
        displayMenuItems(filteredItems);
    }

    // Cart functions
    function addToCart(itemId) {
        const item = menuItems.find(i => i.id === itemId);
        if (!item) return;
        if (item.stock === 0) {
            alert('This item is out of stock!');
            return;
        }
        const existingItem = cart.find(i => i.id === itemId);
        if (existingItem) {
            if (existingItem.quantity >= item.stock) {
                alert('Not enough stock available!');
                return;
            }
            existingItem.quantity += 1;
        } else {
            cart.push({ ...item, quantity: 1 });
        }
        updateCartDisplay();
    }

    function removeFromCart(itemId) {
        cart = cart.filter(item => item.id !== itemId);
        updateCartDisplay();
    }

    function updateQuantity(itemId, change) {
        const cartItem = cart.find(i => i.id === itemId);
        const menuItem = menuItems.find(i => i.id === itemId);
        if (!cartItem || !menuItem) return;
        const newQuantity = cartItem.quantity + change;
        if (newQuantity <= 0) {
            removeFromCart(itemId);
        } else if (newQuantity > menuItem.stock) {
            alert('Not enough stock available!');
        } else {
            cartItem.quantity = newQuantity;
            updateCartDisplay();
        }
    }

    function updateCartDisplay() {
        const cartItems = document.getElementById('cartItems');
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (!cartItems) return;
        if (!checkoutBtn) { /* continue but won't toggle */ }

        if (cart.length === 0) {
            cartItems.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <div class="text-4xl mb-4">🛒</div>
                    <p>No items in cart</p>
                </div>`;
            if (checkoutBtn) checkoutBtn.disabled = true;
        } else {
            cartItems.innerHTML = cart.map(item => `
                <div class="cart-item bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-medium text-gray-800">${item.name}</h4>
                        <button class="text-red-500 hover:text-red-700 text-sm" onclick="removeFromCart(${item.id})">✕</button>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <button class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300 transition-colors" onclick="updateQuantity(${item.id}, -1)">-</button>
                            <span class="font-medium">${item.quantity}</span>
                            <button class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300 transition-colors" onclick="updateQuantity(${item.id}, 1)">+</button>
                        </div>
                        <span class="font-semibold text-green-600">₱${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                </div>`).join('');
            if (checkoutBtn) checkoutBtn.disabled = false;
        }
        updateTotals();
    }

    function updateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * 0.085;
        const total = subtotal + tax;

        const elSubtotal = document.getElementById('subtotal');
        const elTax = document.getElementById('tax');
        const elTotal = document.getElementById('total');

    if (elSubtotal) elSubtotal.textContent = `₱${subtotal.toFixed(2)}`;
    if (elTax) elTax.textContent = `₱${tax.toFixed(2)}`;
    if (elTotal) elTotal.textContent = `₱${total.toFixed(2)}`;
    }

    function clearCart() {
        cart = [];
        updateCartDisplay();
    }

    // Process order and update inventory/earnings
    function processOrder() {
        if (cart.length === 0) return;

        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * 0.085;
        const total = subtotal + tax;

        const order = {
            id: dailyEarnings.orders.length + 1,
            timestamp: new Date(),
            items: cart.map(i => ({ id: i.id, name: i.name, price: i.price, quantity: i.quantity })),
            subtotal,
            tax,
            total,
            itemCount: cart.reduce((sum, item) => sum + item.quantity, 0)
        };

        // Update daily earnings
        dailyEarnings.totalSales += subtotal;
        dailyEarnings.totalTax += tax;
        dailyEarnings.totalOrders += 1;
        dailyEarnings.totalItemsSold += order.itemCount;
        dailyEarnings.orders.push(order);

        // Update items sold tracking
        cart.forEach(cartItem => {
            dailyEarnings.itemsSold[cartItem.name] = (dailyEarnings.itemsSold[cartItem.name] || 0) + cartItem.quantity;
        });

        // Update inventory
        cart.forEach(cartItem => {
            const menuItem = menuItems.find(item => item.id === cartItem.id);
            if (menuItem) menuItem.stock = Math.max(0, menuItem.stock - cartItem.quantity);
        });

    alert(`Order #${order.id} processed successfully!\nTotal: ₱${total.toFixed(2)}\nInventory and earnings updated!`);

        // Clear cart and refresh displays
        clearCart();
        displayMenuItems(currentCategory === 'all' ? menuItems : menuItems.filter(item => item.category === currentCategory));
        updateInventoryTable();
        updateEarningsDisplay();
    }

    function updateEarningsDisplay() {
        const elSales = document.getElementById('totalSales');
        const elTax = document.getElementById('totalTax');
        const elOrders = document.getElementById('totalOrders');
        const elItems = document.getElementById('totalItemsSold');

    if (elSales) elSales.textContent = `₱${dailyEarnings.totalSales.toFixed(2)}`;
    if (elTax) elTax.textContent = `₱${dailyEarnings.totalTax.toFixed(2)}`;
    if (elOrders) elOrders.textContent = dailyEarnings.totalOrders;
    if (elItems) elItems.textContent = dailyEarnings.totalItemsSold;
    }

    function updateOrderHistory() {
        const orderHistory = document.getElementById('orderHistory');
        if (!orderHistory) return;

        if (dailyEarnings.orders.length === 0) {
            orderHistory.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <div class="text-4xl mb-4">📋</div>
                    <p>No orders processed yet today</p>
                </div>`;
            return;
        }

        const recentOrders = dailyEarnings.orders.slice(-10).reverse();
        orderHistory.innerHTML = recentOrders.map(order => `
            <div class="order-item bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h4 class="font-medium text-gray-800">Order #${order.id}</h4>
                        <p class="text-sm text-gray-600">${new Date(order.timestamp).toLocaleTimeString()}</p>
                    </div>
                    <span class="font-semibold text-green-600">₱${order.total.toFixed(2)}</span>
                </div>
                <div class="text-sm text-gray-600">
                    ${order.itemCount} items • ${order.items.map(item => `${item.quantity}x ${item.name}`).join(', ')}
                </div>
            </div>`).join('');
    }

    function updateTopSellingItems() {
        const topSellingItems = document.getElementById('topSellingItems');
        if (!topSellingItems) return;

        if (Object.keys(dailyEarnings.itemsSold).length === 0) {
            topSellingItems.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <div class="text-4xl mb-4">🏆</div>
                    <p>No sales data available yet</p>
                </div>`;
            return;
        }

        const sortedItems = Object.entries(dailyEarnings.itemsSold).sort(([,a],[,b]) => b - a).slice(0,5);
        topSellingItems.innerHTML = sortedItems.map(([name, qty], index) => {
            const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}.`;
            return `
                <div class="flex justify-between items-center py-3 border-b border-gray-100 last:border-b-0">
                    <div class="flex items-center space-x-3">
                        <span class="text-lg">${medal}</span>
                        <span class="font-medium text-gray-800">${name}</span>
                    </div>
                    <span class="font-semibold text-blue-600">${qty} sold</span>
                </div>`;
        }).join('');
    }

    // Generate and download daily report
    function generateDailyReport() {
    const report = '';
    // ...existing code...
}

    function resetDailyEarnings() {
        if (confirm('Are you sure you want to reset all daily earnings data? This action cannot be undone.')) {
        }
    }

    // Inventory functions
    function updateInventoryTable() {
        const inventoryTable = document.getElementById('inventoryTable');
        if (!inventoryTable) return;
        const filter = (document.getElementById('stockFilter')?.value) || 'all';

        let filteredItems = menuItems;
        if (filter === 'low') {
            filteredItems = menuItems.filter(item => item.stock > 0 && item.stock <= item.minStock);
        } else if (filter === 'out') {
            filteredItems = menuItems.filter(item => item.stock === 0);
        }

        inventoryTable.innerHTML = filteredItems.map(item => {
            const status = getStockStatus(item);
            let statusBadge = '';

            switch (status) {
                case 'out':
                    statusBadge = '<span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">Out of Stock</span>';
                    break;
                case 'low':
                    statusBadge = '<span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Low Stock</span>';
                    break;
                case 'medium':
                    statusBadge = '<span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">Medium Stock</span>';
                    break;
                default:
                    statusBadge = '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">In Stock</span>';
            }

            return `<tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-4 px-4 font-medium text-gray-800">${item.name}</td>
                <td class="py-4 px-4 text-gray-600 capitalize">${item.category}</td>
                <td class="py-4 px-4 text-gray-600">₱${item.price.toFixed(2)}</td>
                <td class="py-4 px-4">
                    <div class="flex items-center space-x-2">
                        <button class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300 transition-colors text-sm" onclick="updateStock(${item.id}, -1)">-</button>
                        <span class="font-medium w-12 text-center">${item.stock}</span>
                        <button class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300 transition-colors text-sm" onclick="updateStock(${item.id}, 1)">+</button>
                    </div>
                </td>
                <td class="py-4 px-4">${statusBadge}</td>
                <td class="py-4 px-4">
                    <div class="flex space-x-2">
                        <button class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition-colors" onclick="restockItem(${item.id})">Restock</button>
                        <button class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600 transition-colors" onclick="deleteItem(${item.id})">Delete</button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    function updateStock(itemId, change) {
        const item = menuItems.find(i => i.id === itemId);
        if (!item) return;
        item.stock = Math.max(0, item.stock + change);
        updateInventoryTable();

        if (currentTab === 'pos') {
            displayMenuItems(currentCategory === 'all' ? menuItems : menuItems.filter(i => i.category === currentCategory));
        }
    }

    function restockItem(itemId) {
        const item = menuItems.find(i => i.id === itemId);
        if (!item) return;
    const restockAmount = prompt(`Enter restock amount for ${item.name}:`, '20');

        if (restockAmount && !isNaN(restockAmount) && parseInt(restockAmount) > 0) {
            item.stock += parseInt(restockAmount);
            updateInventoryTable();

            if (currentTab === 'pos') {
                displayMenuItems(currentCategory === 'all' ? menuItems : menuItems.filter(i => i.category === currentCategory));
            }

            alert(`${item.name} restocked with ${parseInt(restockAmount)} units!`);
        }
    }

    function restockAll() {
        if (confirm('Restock all items to 50 units?')) {
            menuItems.forEach(item => item.stock = 50);
            updateInventoryTable();
            if (currentTab === 'pos') {
                displayMenuItems(currentCategory === 'all' ? menuItems : menuItems.filter(i => i.category === currentCategory));
            }
            alert('All items have been restocked to 50 units!');
        }
    }

    function deleteItem(itemId) {
        const item = menuItems.find(i => i.id === itemId);
        if (!item) return;
    if (confirm(`Are you sure you want to delete ${item.name}?`)) {
            menuItems = menuItems.filter(i => i.id !== itemId);
            cart = cart.filter(i => i.id !== itemId);

            updateInventoryTable();
            updateCartDisplay();
            displayMenuItems(currentCategory === 'all' ? menuItems : menuItems.filter(i => i.category === currentCategory));
            alert(`${item.name} has been deleted from the menu!`);
        }
    }

    function filterByStock() {
        updateInventoryTable();
    }

    // Add item modal (works if you have #addItemModal and inputs in DOM)
    function showAddItemModal() {
        document.getElementById('addItemModal')?.classList.remove('hidden');
    }

    function closeAddItemModal() {
        document.getElementById('addItemModal')?.classList.add('hidden');
        const fields = ['newItemName','newItemPrice','newItemStock','newItemDescription'];
        fields.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    }

    function addNewItem(event) {
        if (event && event.preventDefault) event.preventDefault();

        const name = document.getElementById('newItemName')?.value || 'New Item';
        const category = document.getElementById('newItemCategory')?.value || 'mains';
        const price = parseFloat(document.getElementById('newItemPrice')?.value || '0');
        const stock = parseInt(document.getElementById('newItemStock')?.value || '0');
        const description = document.getElementById('newItemDescription')?.value || '';

        const newId = menuItems.length ? Math.max(...menuItems.map(item => item.id)) + 1 : 1;

        const newItem = {
            id: newId,
            name,
            price: isNaN(price) ? 0 : price,
            category,
            description,
            stock: isNaN(stock) ? 0 : stock,
            minStock: Math.max(5, Math.floor((isNaN(stock) ? 0 : stock) * 0.2))
        };

        menuItems.push(newItem);
        updateInventoryTable();
        displayMenuItems(currentCategory === 'all' ? menuItems : menuItems.filter(i => i.category === currentCategory));
        closeAddItemModal();
    alert(`${name} has been added to the menu!`);
    }

    // Keyboard support for PIN entry
    document.addEventListener('keydown', function(e) {
        const loginScreen = document.getElementById('loginScreen');
        if (!loginScreen || loginScreen.classList.contains('hidden')) return;

        const key = e.key;
        if (key >= '0' && key <= '9') {
            addPin(key);
        } else if (key === 'Enter') {
            submitPin();
        } else if (key === 'Backspace' || key === 'Delete') {
            clearPin();
        }
    });