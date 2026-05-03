Goal Description
The frontend is repeatedly encountering a Network Error (ERR_EMPTY_RESPONSE) when trying to fetch data from the Symfony backend at /api/movies. The goal is to identify why the backend drops the connection, fix the root cause, and optimize the frontend's data fetching strategy to make it more resilient. Since the goal is to handle a large database of movies, we need an approach that scales well without returning massive payloads all at once.

User Review Required
Please review the new plan which allows displaying all movies by optimizing the data sent by the server.

Proposed Changes
Backend (
docker-compose.yml
 & 
MovieController.php
)
Fix connection drop: The PHP built-in server is single-threaded and drops concurrent requests (common when React Strict Mode sends 2 requests at once). We will add PHP_CLI_SERVER_WORKERS: 4 to the backend environment in 
docker-compose.yml
 so it can handle multiple concurrent connections.
Optimize query payload (Lightweight List): Instead of sending the full heavy cast and platforms arrays for every movie in the list (which bloats the payload and slows down serialization), we will remove these fields from the api_movies_list endpoint. The frontend only needs cast when viewing a single movie's details. The api_movies_show endpoint will still return the full data. This drastically reduces the response size and time, allowing us to fetch hundreds of movies quickly.
Frontend (
MovieContext.tsx
)
Secure Optimize Data Fetching: We will not use sessionStorage for security reasons. The data will remain strictly in React's protected internal memory. To prevent the "Network Error" on page load (often caused by React Strict Mode sending duplicate requests), we will implement an AbortController to cleanly cancel redundant requests without overwhelming the backend.
Verification Plan
Manual Verification
After applying changes, I will restart the backend Docker service.
I will verify the API using curl to ensure it responds quickly even for 200+ movies.
The user can verify on the React app by refreshing the page and checking the Network tab (it should only fetch once, then load from cache).