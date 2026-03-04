import { ROOM_NAME, AUTH_TOKEN } from './dyteConst.js';

// Expects DyteClient to be imported from web-core and
// initializes an instance of a meeting.
export async function initializeDyteMeeting() {
    return await DyteClient.init({
        authToken: AUTH_TOKEN,
        roomName: ROOM_NAME,
        defaults: {
          audio: true,
          video: true,
        },
    });
}

// Adds a given meeting to all html elements in a given
// document which have the class '.dyte'.
export function addDyteMeetingToDyteClassMembers(document, dyteMeeting) {
    [...document.getElementsByClassName('dyte')].forEach( (node) => {
        node.meeting = dyteMeeting;

        if(node.nodeName === 'DYTE-CONTROLBAR'){
            node.disableRender = true
        }
        console.log(node)
    });

}


export function addDyteConfigToDyteClassMembers(document, dyteConfig) {
    [...document.getElementsByClassName('dyte')].forEach( (node) => {
        node.config = dyteConfig;
    });
}
