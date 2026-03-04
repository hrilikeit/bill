let currentPage = 1;
let currentType = "";
let paginationInfo = null;
let currentMessages = null;

function changeType() {
    const buttons = document.querySelectorAll('.button-container a');
    if (buttons) {
        buttons.forEach(button => {
            button.addEventListener("click", async function (e) {

                buttons.forEach(btn => btn.classList.remove("active"));
                this.classList.toggle('active');
                e.preventDefault();
                const message_type = e.currentTarget.dataset.type;
                currentPage = 1;
                currentType = message_type;
                await loadMessages();
                renderPagination(paginationInfo);
                renderMessages(currentMessages);
            });
        });
    }
}

async function loadMessages() {
    await fetch("/?mode=Api&job=get_paginated_messages&type=" + currentType + "&page=" + currentPage, {
        method: "GET",
    })
        .then(response => response.text())
        .then(data => {
            const parsedInfo = JSON.parse(data);
            console.log(parsedInfo);
            paginationInfo = parsedInfo.pagination;
            currentMessages = parsedInfo.messages;
        })
        .catch(error => {
            console.error("Error:", error);
        });
}


function renderPagination(paginationInfo) {
    const totalPages = paginationInfo.total_pages;
    const pagination = document.getElementById('paginationContainer');
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
    container.innerHTML = '';

    if(messages.length > 0) {

        messages.forEach(message => {

            const link = document.createElement('a');
            link.href = message.link;

            const wrapper = document.createElement('div');
            wrapper.className = message.is_read
                ? 'private_message_selector'
                : 'private_message_selector_unread';

            wrapper.innerHTML = `
            <div style="margin-left: 45px;">
                <strong>${message.name}</strong><br>
            </div>
            <img src="${message.avatar}" class="tiny_avatar" alt="">
            ${message.from ? 'From ' + message.from : 'To ' + message.to} <br>
            <div class="private_message_selector_unread_time">
                On ${message.send_time}
            </div>
                       
        `;

            link.appendChild(wrapper);
            container.appendChild(link);
        });
        return;
    }

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
    }

    const emptyTextElement = document.createElement('p').innerHTML = emptyText;
    container.append(emptyTextElement);
}

async function goToPage(pageNumber = "0") {
    currentPage = pageNumber;
    await loadMessages();
    renderPagination(paginationInfo);
    renderMessages(currentMessages);
}

document.addEventListener("DOMContentLoaded", function () {
    changeType();
});

