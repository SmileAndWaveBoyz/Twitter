document.addEventListener('DOMContentLoaded', () => {
    const tweetForm = document.querySelector('.tweet__form')
    const tweetInput = document.querySelector('.tweet__input')

    if (tweetForm) {
        //Expand the new tweet form when the input is clicked
        tweetForm.addEventListener('click', ()=> {
            tweetForm.classList.add('active')
        })

        //Close the new tweet form when clicking outside of it
        document.addEventListener('click', (e) =>{
            if (!tweetForm.contains(e.target) && !tweetInput.contains(e.target)) {
                tweetForm.classList.remove('active')
            }
        })

        //Add a new tweet when the form is submitted
        tweetForm.addEventListener('submit', (e)=>{
            e.preventDefault()
            fetch(`${dataVar.apiUrl}wp/v2/tweet`, {
                method: 'POST',
                headers:{
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dataVar.nonce,
                },
                body: JSON.stringify({
                    title: tweetInput.value.substring(0, 10),
                    content: tweetInput.value,
                    status: 'publish',
                }),
            })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to post tweet.')
                }
                return response.json()
            })
            .then((data) => {
                const date = new Date().toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                })
                const author = document.querySelector('.tweet__author')

                const template = `
                    <div class="tweet">
                        <div class="tweet__titleContainer">
                            <p>
                                <strong>${author.value}</strong>
                                ${date}
                            </p>
                            <div class="tweet__buttons">
                                <button class="tweet__button delete" data-tweet-id="${data.id}">Delete</button>
                                <button class="tweet__button edit" data-tweet-id="${data.id}">Edit</button>
                            </div>
                        </div>
                        <p class="tweet__content">${tweetInput.value}</p>
                    </div>
                `;
                tweetForm.insertAdjacentHTML('afterend', template)
                tweetInput.value = ''
            })
            .catch(error => console.log(error))
        })
    }

    //Delete tweet button
    document.addEventListener('click', (e)=>{
        if (e.target && e.target.classList.contains('delete')) {
            const tweetId = e.target.dataset.tweetId
            
            fetch(`${dataVar.apiUrl}wp/v2/tweet/${tweetId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dataVar.nonce
                }
            })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Failed to delete tweet");
                }
                return response
            })
            .then((data) => {
                e.target.closest('.tweet').classList.add('tweet-deleting')
                setTimeout(() => {
                    e.target.closest('.tweet').remove()
                }, 300);
            })
        }
    })
    
    //Edit tweet button
    let editingTweet = false
    let originalContent = ''
    document.addEventListener('click', (e)=> {
        
        if (e.target && e.target.classList.contains('edit')) {            
            const tweetId = e.target.dataset.tweetId
            editingTweet = e.target.closest('.tweet')
            const tweetContent = editingTweet.querySelector('.tweet__content')
            
            //Add this class to the tweet element to remove the edit button
            editingTweet.classList.add('tweet-editing')
            originalContent = tweetContent.innerText

            tweetContent.innerHTML = `
                <textarea class="tweet__editArea">${originalContent}</textarea>
                <button class="tweet__button save" data-tweet-id="${tweetId}">Save</button>
            `
        } else if(editingTweet && e.target && !e.target.classList.contains('tweet__editArea') && !e.target.classList.contains('save')){
            //Stop editing tweet when clicking outside of it
            editingTweet.classList.remove('tweet-editing')
            editingTweet.querySelector('.tweet__content').innerHTML = originalContent
            editingTweet = false
            originalContent = ''
        }
    })

    //Save edited tweet button
    document.addEventListener('click', (e)=>{
        if (e.target && e.target.classList.contains('save')) {
            const tweetId = e.target.dataset.tweetId
            const tweet = e.target.closest('.tweet')
            const tweetEditArea = tweet.querySelector('.tweet__editArea').value
            
            fetch(`${dataVar.apiUrl}wp/v2/tweet/${tweetId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dataVar.nonce
                },
                body: JSON.stringify({content: tweetEditArea})
            })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Failed to Update tweet");
                }
                return response.json()
            })
            .then((data) => {
                //Replace the textarea with the updated content
                tweet.querySelector('.tweet__content').innerText = tweetEditArea
                tweet.classList.remove('tweet-editing')
                e.target.remove()
            })
            .catch(error => console.log(error))
        }
    })

    //Search input functionality
    const searchInput = document.querySelector('.navigationBar__search')
    const searchResultsContainer = document.querySelector('.navigationBar__searchResults')
    if (searchInput) {
        searchInput.addEventListener('input', (e)=>{
            if (searchInput.value.length > 0) {
                fetch(`${dataVar.apiUrl}mytheme/v1/users?search=${searchInput.value}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': dataVar.nonce,
                    }
                })
                .then(response => response.json())
                .then((data) => {
                    searchResultsContainer.innerHTML = `
                        <ul class="navigationBar__searchResults-list">
                            ${
                                data.map((user) => {
                                    return`
                                        <li class="navigationBar__searchResults-item">
                                            <p class="navigationBar__searchuser">${user.name}</p>
                                            ${user.is_friend ? `<button class="tweet__button unfriend" data-user-id="${user.id}">Unfriend</button>` : `<button class="tweet__button addFriend" data-user-id="${user.id}">Add Friend</button>`}
                                        </li>
                                    `
                                }).join('')
                            }
                        </ul>
                    `
                })
                .catch(error => console.log(error))
            }
        })

        //Close the search results container when clicking outside of it
        document.addEventListener('click', (e)=>{
            if (searchResultsContainer && searchInput && !searchResultsContainer.contains(e.target) && !searchInput.contains(e.target)) {
                searchResultsContainer.innerHTML = ''
            }
        })
    }

    //Add friend button functionality
    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('addFriend')) {
            const friendID = e.target.dataset.userId

            fetch(`${dataVar.apiUrl}mytheme/v1/add-friend-request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dataVar.nonce,
                },
                body: JSON.stringify({
                    friend_id: friendID,
                })
            })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to add friend.')
                }
                return response.json
            })
            .then((data) => {
                e.target.innerText = 'Request sent'
                //Disable the button after sending the request
                e.target.disabled = true
                e.target.classList.add('disabled')
            })
            .catch(error => console.log(error))
        }
    })

    //View friend requests button
    const viewFriendRequestButton = document.querySelector('.navigationBar__listItem.friendRequests')
    const friendRequestContainer = document.querySelector('.navigationBar__friendRequests')
    if (viewFriendRequestButton) {
        viewFriendRequestButton.addEventListener('click', (e) =>{
            friendRequestContainer.classList.toggle('active')
        })
    }

    // Close the friend requests container when clicking outside of it
    document.addEventListener('click', (e)=> {
        if (friendRequestContainer && !friendRequestContainer.contains(e.target) && !viewFriendRequestButton.contains(e.target)) {
            if (friendRequestContainer) {
                friendRequestContainer.classList.remove('active'); // Hide the friend requests container.
            }
        }
    })

    // Accept a friend request
    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('navigationBar__friendRequestAccept')) {
            const button = e.target
            const friendRequestId = button.dataset.friendRequestId

            fetch(`${dataVar.apiUrl}mytheme/v1/accept-friend-request?friendRequestId=${friendRequestId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dataVar.nonce,
                },
            })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Failed to accept friend request.")
                }
                return response.json()
            })
            .then((data) => {
                // Disable the button
                button.innerText = 'Accepted'
                button.disabled = true
                button.classList.add('disabled')

                // Hide the decline button, assuming it's to the right of the accept button.
                if (button.nextElementSibling) {
                    button.nextElementSibling.style.display = 'none'
                }

                // Reduce the number of friend requests displayed and hide the count if it reaches zero.
                const friendRequestCount = document.querySelector('.numberOfFriendRequests')
                if (friendRequestCount) {
                    friendRequestCount.innerText = parseInt(friendRequestCount.innerText) - 1
                    if (friendRequestCount.innerText <= 0) {
                        friendRequestCount.style.display = 'none'
                    }
                }
            })
            .catch(error => console.log(error))
        }
    })

    // Reject a friend request
    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('navigationBar__friendRequestDecline')) {
            const button = e.target;
            const friendRequestId = button.dataset.friendRequestId;

            fetch(`${dataVar.apiUrl}mytheme/v1/reject-friend-request?friendRequestId=${friendRequestId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dataVar.nonce,
                },
            })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Failed to reject friend request.");
                }
                return response.json();
            })
            .then((data) => {
                // Disable the button
                button.innerText = 'Rejected';
                button.disabled = true;
                button.classList.add('disabled');

                // Hide the accept button, assuming it's to the left of the decline button.
                if (button.previousElementSibling) {
                    button.previousElementSibling.style.display = 'none';
                }

                // Reduce the number of friend requests displayed and hide the count if it reaches zero.
                const friendRequestCount = document.querySelector('.numberOfFriendRequests');
                if (friendRequestCount) {
                    friendRequestCount.innerText = parseInt(friendRequestCount.innerText) - 1;
                    if (friendRequestCount.innerText <= 0) {
                        friendRequestCount.style.display = 'none';
                    }
                }
            })
            .catch(error => console.log(error));
        }
    })

    //Unfriend button
    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('unfriend')) {
            // The friends ID, not the friend request ID, in this way it is different to the accept and reject friend request buttons.
            const friendId = e.target.dataset.userId

            fetch(`${dataVar.apiUrl}mytheme/v1/unfriend`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dataVar.nonce,
                },
                body: JSON.stringify({ friend_id: friendId })
            })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Failed to unfriend.")
                }
                return response.json();
            })
            .then((data) => {
                //Disable the button
                e.target.innerText = 'Unfriended'
                e.target.disabled = true
                e.target.classList.add('disabled')
            })
            .catch(error => console.log(error))
        }
    })

})
