// Messages are loaded via AJAX so switching between Unread / Read / Sent and
// pagination does not require a full page reload.

let currentPage = 1;
let currentType = "";
let paginationInfo = null;
let currentMessages = [];

// True if the user explicitly requested a type via URL parameter (?type=read etc).
let initialTypeFromUrl = false;

// Used to prevent race conditions when users click quickly between tabs/pages.
let activeRequestController = null;

function changeType() {
    const buttons = document.querySelectorAll('.button-container a');
    if (buttons) {
        buttons.forEach(button => {
            button.addEventListener("click", async function (e) {

                buttons.forEach(btn => btn.classList.remove("active"));
                this.classList.add('active');
                e.preventDefault();
                const message_type = e.currentTarget.dataset.type;
                currentPage = 1;
                currentType = (message_type || '').toLowerCase();
                updateHeading();
                await refreshMessages();
            });
        });
    }
}

function highlightCurrentTypeTab() {
    const buttons = document.querySelectorAll('.button-container a');
    if (!buttons) return;

    buttons.forEach(btn => {
        if ((btn.dataset.type || '').toLowerCase() === (currentType || '').toLowerCase()) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

function updateHeading() {
    const heading = document.getElementById('messagesHeading');
    if (!heading) return;

    const type = (currentType || '').toLowerCase();
    if (type === 'read') {
        heading.className = 'inbox_read';
        heading.textContent = 'Read Messages';
    } else if (type === 'sent') {
        heading.className = 'inbox_sent';
        heading.textContent = 'Sent Messages';
    } else {
        // Default to unread styling/text.
        heading.className = 'inbox_new';
        heading.textContent = 'New Messages';
    }
}

async function loadMessages() {
    // Abort any in-flight request.
    if (activeRequestController && typeof activeRequestController.abort === 'function') {
        activeRequestController.abort();
    }

    const supportsAbort = (typeof AbortController !== 'undefined');
    activeRequestController = supportsAbort ? new AbortController() : null;

    const url = "/?mode=Api&job=get_paginated_messages" +
        "&type=" + encodeURIComponent(currentType) +
        "&page=" + encodeURIComponent(currentPage);

    try {
        const response = await fetch(url, {
            method: "GET",
            headers: {
                "Accept": "application/json"
            },
            credentials: "same-origin",
            signal: activeRequestController ? activeRequestController.signal : undefined
        });

        if (!response.ok) {
            throw new Error("Request failed with status " + response.status);
        }

        const parsedInfo = await response.json();
        paginationInfo = parsedInfo && parsedInfo.pagination ? parsedInfo.pagination : null;
        currentMessages = parsedInfo && Array.isArray(parsedInfo.messages) ? parsedInfo.messages : [];
    } catch (error) {
        // Ignore abort errors (they happen when the user clicks quickly).
        if (error && error.name === 'AbortError') {
            return;
        }
        console.error("Error:", error);
        paginationInfo = null;
        currentMessages = [];

        const container = document.getElementById('messagesContainer');
        if (container) {
            container.innerHTML = '';
            const p = document.createElement('p');
            p.textContent = 'Unable to load messages. Please refresh the page and try again.';
            container.appendChild(p);
        }
        const pagination = document.getElementById('paginationContainer');
        if (pagination) {
            pagination.innerHTML = '';
        }
    }
}


function renderPagination(paginationInfo) {
    const pagination = document.getElementById('paginationContainer');
    if (!pagination) return;

    if (!paginationInfo || !paginationInfo.total_pages || paginationInfo.total_pages <= 1) {
        pagination.innerHTML = '';
        return;
    }

    const totalPages = paginationInfo.total_pages;
    pagination.innerHTML = '';

    const delta = 2;
    const range = [];

    for (let i = 1; i <= totalPages; i++) {
        if (
            i === 1 ||
            i === totalPages ||
            (i >= currentPage - delta && i <= currentPage + delta)
        ) {
            range.push(i);
        }
    }

    let lastPage = 0;

    range.forEach(page => {
        if (page - lastPage > 1) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            pagination.appendChild(dots);
        }

        const btn = document.createElement('button');
        btn.textContent = page;
        btn.classList.add('pagination-page-btn');
        btn.addEventListener('click', page => {
            goToPage(Number(page.target.textContent));
        })

        if (page === currentPage) {
            btn.classList.add('pagination-active');
        }
        pagination.appendChild(btn);
        lastPage = page;
    });
}


function renderMessages(messages) {

    const container = document.getElementById('messagesContainer');
    if (!container) return;

    container.innerHTML = '';

    if (messages && messages.length > 0) {
        messages.forEach(message => {
            // Keep the selector styling consistent with the legacy UI:
            // - Inbox (Unread/Read): use "private_message_selector_unread"
            // - Sent: use "private_message_selector"
            const wrapper = document.createElement('div');
            wrapper.className = ((currentType || '').toLowerCase() === 'sent')
                ? 'private_message_selector'
                : 'private_message_selector_unread';

            // IMPORTANT: legacy CSS expects the <a> to be INSIDE the wrapper.
            // Previously we wrapped the <div> inside the <a>, which caused link text
            // to inherit the global A{color:#fff} and appear "blank".
            const link = document.createElement('a');
            link.href = message.link;
            link.style.display = 'block';

            // Avatar (float left like the original design)
            const img = document.createElement('img');
            img.src = message.avatar || '';
            img.className = 'tiny_avatar';
            img.alt = '';
            img.style.cssText = 'float:left; margin:0 10px 0 10px;';

            // Text content
            const textWrap = document.createElement('div');
            textWrap.style.overflow = 'hidden';

            const strong = document.createElement('strong');
            strong.textContent = message.name || '';
            // Override legacy float styling so the subject doesn't break layout.
            strong.style.cssText = 'float:none; width:auto; display:block; margin-bottom:3px;';

            const fromToText = (message.from && message.from.length)
                ? ('From ' + message.from)
                : ('To ' + (message.to || ''));

            const fromTo = document.createElement('div');
            fromTo.textContent = fromToText;

            const timeDiv = document.createElement('div');
            timeDiv.className = 'private_message_selector_unread_time';
            timeDiv.textContent = 'On ' + (message.send_time || '');

            textWrap.appendChild(strong);
            textWrap.appendChild(fromTo);
            textWrap.appendChild(timeDiv);

            link.appendChild(img);
            link.appendChild(textWrap);
            wrapper.appendChild(link);
            container.appendChild(wrapper);
        });
        return;
    }

    let emptyText = '';
    switch (currentType) {
        case 'unread':
            emptyText = 'You have no messages in your Inbox.';
            break;

        case 'read':
            emptyText = 'You have no read messages.';
            break;

        case 'sent':
            emptyText = 'You have no sent messages.';
            break;

        default:
            emptyText = 'No messages found.';
            break;
    }

    const emptyTextElement = document.createElement('p');
    emptyTextElement.textContent = emptyText;
    container.appendChild(emptyTextElement);
}

async function goToPage(pageNumber = "0") {
    currentPage = pageNumber;
    await refreshMessages(false);
}

async function refreshMessages(autoFallback = false) {
    const container = document.getElementById('messagesContainer');
    if (container) {
        container.innerHTML = '';
        const p = document.createElement('p');
        p.textContent = 'Loading...';
        container.appendChild(p);
    }

    await loadMessages();
    renderPagination(paginationInfo);
    renderMessages(currentMessages);

    // On the first load only (autoFallback=true):
    // show Read if there are no Unread messages, and show Sent if there are no Read.
    if (autoFallback && !initialTypeFromUrl && (!currentMessages || currentMessages.length === 0)) {
        const type = (currentType || '').toLowerCase();
        let nextType = '';
        if (type === 'unread') {
            nextType = 'read';
        } else if (type === 'read') {
            nextType = 'sent';
        }

        if (nextType) {
            currentType = nextType;
            currentPage = 1;
            highlightCurrentTypeTab();
            updateHeading();
            await loadMessages();
            renderPagination(paginationInfo);
            renderMessages(currentMessages);
        }
    }
}

function setInitialTypeAndPage() {
    // Prefer URL params if present, otherwise use the active tab (or fall back to "unread").
    const params = new URLSearchParams(window.location.search);
    const urlType = params.get('type');
    const urlPage = parseInt(params.get('page') || '', 10);

    initialTypeFromUrl = !!(urlType && urlType.length);

    const activeBtn = document.querySelector('.button-container a.active') || document.querySelector('.button-container a[data-type]');
    const domType = activeBtn && activeBtn.dataset ? activeBtn.dataset.type : '';

    // If there are no unread messages, default to "read" so the user doesn't think
    // they have *no* messages (legacy UI showed both sections on one page).
    let defaultType = (domType || 'unread').toLowerCase();
    if (!initialTypeFromUrl) {
        const unreadCountEl = document.getElementById('dyn_Message');
        const unreadCount = unreadCountEl ? parseInt((unreadCountEl.textContent || '').trim(), 10) : NaN;
        if (!isNaN(unreadCount) && unreadCount <= 0) {
            defaultType = 'read';
        }
    }

    currentType = (urlType || defaultType || 'unread').toLowerCase();
    currentPage = (!isNaN(urlPage) && urlPage > 0) ? urlPage : 1;

    highlightCurrentTypeTab();
    updateHeading();
}

document.addEventListener("DOMContentLoaded", function () {
    // Only run on the Messages page.
    if (!document.getElementById('messagesContainer')) {
        return;
    }
    changeType();
    setInitialTypeAndPage();
    refreshMessages(true);
});

