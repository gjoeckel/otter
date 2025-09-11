Getting Quiz Submission Dates via Canvas LMS APIThis document provides a comprehensive guide for developers on how to use the Canvas LMS REST API. It covers two primary tasks: retrieving a list of all students in a course, and then getting the specific dates and times those students submitted a particular quiz.Part 1: Retrieving a List of Students in a CourseBefore fetching quiz submissions, you might want a list of all students in the course. This allows you to correlate the user_id from the submission data with actual student names.API EndpointHTTP Method: GETEndpoint URL:/api/v1/courses/:course_id/users
Parameters: To filter the list to include only students, you must include the enrollment_type[] parameter:enrollment_type[]=studentExample Request (using cURL)curl -X GET "https://<your-canvas-instance>[.instructure.com/api/v1/courses/12345/users?enrollment_type](https://.instructure.com/api/v1/courses/12345/users?enrollment_type)[]=student" \
-H "Authorization: Bearer <YOUR_ACCESS_TOKEN>"
Sample JSON Response SnippetThe API will return a JSON array of user objects.[
  {
    "id": 101,
    "name": "Jane Doe",
    "sortable_name": "Doe, Jane",
    "short_name": "Jane"
  },
  {
    "id": 102,
    "name": "John Smith",
    "sortable_name": "Smith, John",
    "short_name": "John"
  }
]
Part 2: Retrieving Quiz Submission DatesThis section details how to get the submission timestamps for a specific quiz.PrerequisitesBefore you begin, ensure you have the following essential items:Valid Access Token: You'll need an API access token from a Canvas user who has the necessary permissions to view grades and submissions in the course. This can be generated from the user's profile settings in Canvas.Course ID: The unique identifier for the course containing the quiz. You can find this in the course URL (e.g., https://<your-canvas-instance>.instructure.com/courses/COURSE_ID).Quiz ID: The unique identifier for the quiz. This is also found in the quiz's URL (e.g., .../courses/COURSE_ID/quizzes/QUIZ_ID).API EndpointThe primary endpoint we will use is for listing quiz submissions. This endpoint returns a list of all submission objects for a given quiz, which includes detailed information about each student's attempt, including the timestamp.HTTP Method: GETEndpoint URL:/api/v1/courses/:course_id/quizzes/:quiz_id/submissions
You will need to replace :course_id and :quiz_id with your specific IDs.Request StructureYour API request will consist of the endpoint URL and an authorization header containing your access token.Headers:Authorization: Bearer <YOUR_ACCESS_TOKEN>Example Request (using cURL):curl -X GET "https://<your-canvas-instance>[.instructure.com/api/v1/courses/12345/quizzes/6789/submissions](https://.instructure.com/api/v1/courses/12345/quizzes/6789/submissions)" \
-H "Authorization: Bearer <YOUR_ACCESS_TOKEN>"
Understanding the API ResponseThe API will return a JSON object containing a quiz_submissions array. Each object inside this array represents a single student's submission attempt.Key Fields in the Response:id: The unique ID of the submission attempt.user_id: The unique ID for the student who made the submission.finished_at: An ISO 8601 formatted string representing the date and time the student completed the quiz submission. This is the field you need for the submission date.started_at: An ISO 8601 formatted string for when the student started the attempt.Sample JSON Response Snippet:{
  "quiz_submissions": [
    {
      "id": 1,
      "quiz_id": 6789,
      "user_id": 101,
      "submission_id": 1,
      "attempt": 1,
      "started_at": "2025-09-10T18:30:00Z",
      "finished_at": "2025-09-10T18:55:21Z",
      "end_at": "2025-09-10T19:30:00Z",
      "time_spent": 1521,
      "score": 89.0,
      "kept_score": 89.0,
      "workflow_state": "pending_review"
    },
    {
      "id": 2,
      "quiz_id": 6789,
      "user_id": 102,
      "submission_id": 2,
      "attempt": 1,
      "started_at": "2025-09-10T20:05:10Z",
      "finished_at": "2025-09-10T20:28:00Z",
      "end_at": "2025-09-10T21:05:10Z",
      "time_spent": 1370,
      "score": 95.0,
      "kept_score": 95.0,
      "workflow_state": "graded"
    }
  ]
}
Code Example (Python)Here is a complete Python script that demonstrates how to make the API call and process the response to get a list of user IDs and their corresponding submission dates.import requests
import json

# --- Configuration ---
CANVAS_DOMAIN = "https://<your-canvas-instance>.instructure.com" # e.g., "[https://canvas.example.com](https://canvas.example.com)"
ACCESS_TOKEN = "<YOUR_ACCESS_TOKEN>"
COURSE_ID = "12345" # Replace with your course ID
QUIZ_ID = "6789"    # Replace with your quiz ID

# --- API Request Setup ---
API_URL = f"{CANVAS_DOMAIN}/api/v1/courses/{COURSE_ID}/quizzes/{QUIZ_ID}/submissions"
HEADERS = {
    "Authorization": f"Bearer {ACCESS_TOKEN}"
}

# --- Main Function ---
def get_quiz_submission_dates():
    """
    Fetches quiz submissions and prints the user ID and submission timestamp.
    """
    try:
        response = requests.get(API_URL, headers=HEADERS)
        response.raise_for_status()  # Raises an HTTPError for bad responses (4xx or 5xx)

        submissions_data = response.json()
        quiz_submissions = submissions_data.get("quiz_submissions", [])

        if not quiz_submissions:
            print("No submissions found for this quiz.")
            return

        print(f"Submission Dates for Quiz ID: {QUIZ_ID} in Course ID: {COURSE_ID}\n")

        for submission in quiz_submissions:
            user_id = submission.get("user_id")
            finished_at = submission.get("finished_at")

            if user_id and finished_at:
                print(f"  User ID: {user_id} -> Submitted at: {finished_at}")
            else:
                print(f"  Incomplete data for one submission: {submission}")

    except requests.exceptions.RequestException as e:
        print(f"An error occurred during the API request: {e}")
    except json.JSONDecodeError:
        print("Failed to parse the JSON response from the server.")
    except KeyError:
        print("Unexpected structure in the API response.")


if __name__ == "__main__":
    get_quiz_submission_dates()
How to Run the Python Script:Make sure you have the requests library installed (pip install requests).Replace the placeholder values in the "Configuration" section with your actual Canvas domain, access token, course ID, and quiz ID.Save the code as a Python file (e.g., get_submissions.py) and run it from your terminal: python get_submissions.py.Troubleshooting401 Unauthorized: Your access token is likely invalid, expired, or does not have the correct permissions.404 Not Found: Double-check that your course_id and quiz_id are correct. Also, ensure the access token belongs to a user enrolled in the course.Empty Response: This could mean there are no submissions for the quiz yet, or the user associated with the token cannot view the submissions.