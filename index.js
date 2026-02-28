// ============================================
// BADGE DATA (from PHP/database or fallback)
// ============================================
const rawBadges = window.GOOD_MORNING_BADGES || [];
const usernames = rawBadges.map(function(b) {
    return typeof b === 'string' ? b : { username: b.username || b.instagram_id || '', name: b.name || '', image: b.image || '' };
});

// ============================================
// BADGE INITIALIZATION (static grid, left-to-right, flow down)
// ============================================
function initializeBadges() {
    const $badgeLayer = $("#badge-layer");
    $badgeLayer.empty();

    const badgeCount = Math.min(usernames.length, 200);
    const selectedUsernames = usernames.slice(0, badgeCount);

    selectedUsernames.forEach(function(item) {
        const isObj = typeof item === 'object';
        const username = (isObj ? item.username : item) || '';
        const name = isObj ? (item.name || '') : '';
        const image = isObj ? (item.image || '') : '';
        const searchText = (username + ' ' + name).toLowerCase();
        let html = '';
        if (image) {
            html += '<img class="badge-img" src="' + image.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" alt="">';
        } else {
            html += '<span class="badge-placeholder"></span>';
        }
        html += '<span class="badge-text">' + (username || '').replace(/</g, '&lt;') + (name ? ' ' + name.replace(/</g, '&lt;') : '') + '</span>';
        const $badge = $('<div class="badge' + (image ? ' has-img' : '') + '" data-search="' + searchText.replace(/"/g, '&quot;') + '">').html(html);
        $badgeLayer.append($badge);
    });
}

// ============================================
// SEARCH / FILTER BADGES
// ============================================
function applySearchFilter(query) {
    const q = (query || "").trim().toLowerCase();
    $(".badge").each(function() {
        const text = ($(this).data("search") || $(this).text()).toLowerCase();
        const match = !q || text.includes(q);
        $(this).toggleClass("matched", match && q.length > 0).toggleClass("hidden", !match);
    });
}

// ============================================
// INITIALIZATION ON DOCUMENT READY
// ============================================
$(document).ready(function() {
    let allUsers = [];

    // Fetch user data from PHP API
    function fetchUsers() {
        $.ajax({
            url: 'api.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                allUsers = data;
                displayUsers(allUsers);
            },
            error: function() {
                $('#badge-layer').html('<p>Error loading users</p>');
            }
        });
    }

    // Display users with rounded images
    function displayUsers(users) {
        const origin = window.location.origin;
        let html = '';
        users.forEach(user => {
            let imgSrc = (user.image && String(user.image).trim()) ? user.image : '';
            if (imgSrc && !/^https?:\/\//.test(imgSrc)) {
                imgSrc = origin + (imgSrc.charAt(0) === '/' ? '' : '/') + imgSrc;
            }
            const imgTag = imgSrc
                ? '<img src="' + imgSrc.replace(/"/g, '&quot;') + '" alt="" class="user-image" loading="lazy" decoding="async">'
                : '<div class="user-image user-image-placeholder">' + (user.username ? user.username.charAt(0).toUpperCase() : '?') + '</div>';
            const searchText = ((user.username || '') + ' ' + (user.name || '')).toLowerCase();
            html += '<div class="user-card" data-username="' + (user.username || '').toLowerCase().replace(/"/g, '&quot;') + '" data-search="' + searchText.replace(/"/g, '&quot;') + '">' + imgTag + '</div>';
        });
        $('#badge-layer').html(html);
    }

    // Search and highlight matching usernames (keep all visible, only highlight matches)
    $('#search-input').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();

        $('.user-card').each(function() {
            const searchText = ($(this).data('search') || $(this).data('username') || '').toLowerCase();
            const match = !searchTerm || searchText.includes(searchTerm);
            $(this).toggleClass('highlighted', match && searchTerm.length > 0);
        });
    });

    // Initial load
    fetchUsers();
});