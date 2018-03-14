from datetime import datetime, timedelta
from pushEvent import pushEvent
import time
import requests
import json

class Assignment:

        def __init__(self, assignmentName=None, courseID=None, dueDate=None, closeDate=None, pointsPossible=None, url=None):
                self.assignmentName = assignmentName
		self.courseID = courseID
                self.dueDate = dueDate
                self.closeDate = closeDate
                self.pointsPossible = pointsPossible
                self.url = url


def main():	
	accessTok = raw_input("Enter your access token: ")

	params = {"access_token": accessTok}
	print("\n")


	response = requests.get("https://sjsu.instructure.com/api/v1/users/self/todo", params=params)

	toDoList = response.json()
	assignmentList = []

	#getting items from the to do list and parsing them
	for i in range(len(toDoList)):
		name = toDoList[i]["assignment"]["name"]
		course_id = toDoList[i]["assignment"]["course_id"]
		due_date = toDoList[i]["assignment"]["due_at"]
		closes = toDoList[i]["assignment"]["lock_at"]
		points = toDoList[i]["assignment"]["points_possible"]
		url = toDoList[i]["html_url"]
		assignment = Assignment(name, course_id, due_date, closes, points, url)
		assignmentList.append(assignment)		

	#start getting upcoming items
	params = {"access_token": accessTok}
	print("\n")

	response = requests.get("https://sjsu.instructure.com/api/v1/users/self/upcoming_events", params=params)

	upcomingEvents = response.json()

	#take each upcoming event and start saving those too
	for count in range(len(upcomingEvents)):
		name = upcomingEvents[count]["title"]
		course_id = upcomingEvents[count]["assignment"]["course_id"]
		due_date = upcomingEvents[count]["end_at"]
		closes = upcomingEvents[count]["assignment"]["lock_at"]
		points = upcomingEvents[count]["assignment"]["points_possible"]
		url = upcomingEvents[count]["html_url"]
		assignment = Assignment(name, course_id, due_date, closes, points, url)
		if not (any(x.assignmentName == name for x in assignmentList)):
			assignmentList.append(assignment)		

		
	
	#going through each course id retreived and getting its course title
	for j in range(len(assignmentList)):	
		#start getting course objects for each course_id we have
		preparedStatement = "https://sjsu.instructure.com/api/v1/courses/{}".format(assignmentList[j].courseID)
		response = requests.get(preparedStatement, params=params)
		course = response.json()
	
		#Due Date
		#use course object to print name of class and print previously saved info
		print("{} Assignment".format(course["name"]))
		print("Assignment: {}".format(assignmentList[j].assignmentName))

		#format time but also -7 hours because timezone
		datetime_object = datetime.strptime(assignmentList[j].dueDate[:19], "%Y-%m-%dT%H:%M:%S")
		correctTime = datetime_object - timedelta(hours=7)
		formattedStr = str(correctTime)

		ts = time.strptime(formattedStr,"%Y-%m-%d %H:%M:%S")
		finalTime = time.strftime("%m/%d/%Y %I:%M %p",ts)
		print("Due Date: {}".format(finalTime))
	
		#Closes On
		if assignmentList[j].closeDate == None:
			assignmentList[j].closeDate = "Never"
			print("Closes On: Never")
		else:
			#format time but also -7 hours because timezone
			datetime_object = datetime.strptime(assignmentList[j].closeDate[:19], "%Y-%m-%dT%H:%M:%S")
			correctTime = datetime_object - timedelta(hours=7)
			formattedStr = str(correctTime)
	
			ts = time.strptime(formattedStr,"%Y-%m-%d %H:%M:%S")
			finalTime = time.strftime("%m/%d/%Y %I:%M %p",ts)
			print("Closes On: {}".format(finalTime))

		
		#Points Possible
		print("Points Possible: {}".format(assignmentList[j].pointsPossible))

		#Assignment URL
		print("Assignment URL: {}".format(assignmentList[j].url))
		print("\n")

	pushEvent(assignmentList)

if __name__ == "__main__":
	main()		
