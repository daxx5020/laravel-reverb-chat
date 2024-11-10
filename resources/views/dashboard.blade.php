<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
            <button id="enable-notifications" class="mt-5 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Enable Notifications</button>
        </div>
    </div>
</x-app-layout>

<script type="module">
    // Firebase configuration
    const firebaseConfig = {
        apiKey: "AIzaSyA0kvxXXlldqYVZNZOvV-6CjqXzDIunfvg",
        authDomain: "push-notification-a5f33.firebaseapp.com",
        projectId: "push-notification-a5f33",
        storageBucket: "push-notification-a5f33.firebasestorage.app",
        messagingSenderId: "886010659899",
        appId: "1:886010659899:web:3225a8725b5fe78951881e",
        measurementId: "G-HJSCPFJF09"
    };

    // Initialize Firebase
    import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
    import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-messaging.js";

    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);

    // Request permission and get token
    function requestPermission() {
      console.log('Requesting notification permission...');
      Notification.requestPermission().then((permission) => {
        if (permission === 'granted') {
          console.log('Notification permission granted.');
          getTokenFromFirebase();
        } else {
          console.log('Notification permission denied.');
        }
      });
    }

    // Retrieve token from Firebase
    function getTokenFromFirebase() {
    const vapidKey = "BF4bCnBml-pWyy7zHB46RQvGx8m8OZ7Kqbx0mEdQAO68s4YJD4-jZ3A1fnx3lAsECpJeQYkN2ueqMslnMkQHlaQ";

    getToken(messaging, { vapidKey })
        .then((currentToken) => {
            if (currentToken) {
                console.log('FCM Token: ', currentToken);

                // Send the token to the server
            fetch('/save-fcm-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ fcm_token: currentToken })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Token saved:', data);
                })
                .catch((error) => {
                    console.error('Error saving token:', error);
                });
            } else {
                console.log('No registration token available.');
            }
        })
        .catch((err) => {
            console.error('Error while retrieving token:', err);
        });
}


    // Add event listener to the button after defining requestPermission
    document.getElementById("enable-notifications").addEventListener("click", requestPermission);
  </script>

