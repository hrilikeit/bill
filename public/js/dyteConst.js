// To be procured from your backend server
const searchParams = new URL(window.location.href).searchParams;

const authToken = searchParams.get('authToken');
const meetingId = searchParams.get('meeting_id');
const goalStatus = searchParams.get('goal_status');
export const ROOM_NAME=''
export const AUTH_TOKEN= authToken
export const MEETING_ID= meetingId
export const GOAL_STATUS= goalStatus