import { initializeDyteMeeting, addDyteMeetingToDyteClassMembers } from './dyteUtil.js';
import {
  provideDyteDesignSystem,
  extendConfig,
} from 'https://cdn.jsdelivr.net/npm/@dytesdk/ui-kit@1.50.0/dist/esm/index.js';
import { MEETING_ID, GOAL_STATUS } from './dyteConst.js';

let dyteMeeting;
let isLivestraemHost;
let currentUserId;
let videoEl;
let $video;

let GOAL_REACHED = false;
const playerHearts = document.getElementById("player-hearts");
const playerMoney = document.getElementById("player-money");
const player = document.getElementById("player-container");
const goalContainer = document.getElementById("goal-container");
const goalShowContainer = document.getElementById("goalshow-container");

function waitDyteElm(elem, selector, one) {
  //console.log("elem, selector, one", elem, selector, one)
  return new Promise(resolve => {
    if (elem.querySelector(selector)) {
      return resolve(elem.querySelector(selector));
    }

    const observer = new MutationObserver(mutations => {
      if (elem.querySelector(selector)) {
        resolve(elem.querySelector(selector));
        if(!one) observer.disconnect();
      }
    });

    observer.observe(elem, {
      childList: true,
      subtree: true
    });
  });
}

window.goalContainer = null;
window.goalShowContainer = null;

function toggleTipButton() {
  $('#tipModal').modal('show');
}
let $dyteControlbar  = null;



function checkGoalReached() {
  if(!isLivestraemHost){
    //if(true) {
    if(window.goal_current_count >= window.goal_price) {
      GOAL_REACHED = true;
      var formdata = new FormData();
      formdata.append("goal_id", window.goal.id);
      formdata.append("Member_id", memberId);

      var requestOptions = {
        method: 'POST',
        body: formdata,
      };

      fetch(domain+"/?mode=Api&job=get_goal_tip_user", requestOptions)
          .then(response => response.text())
          .then((result) => {
            if(result === 'false'){
              videoEl?.remove();
              window.goalShowContainer.classList.remove('hidden');
            }
          })
    }
  }
}

function checkDyteMeetingElem(showTipButton) {
  const $dyteMeeting = document.querySelector("dyte-meeting");
  waitDyteElm($dyteMeeting.shadowRoot, 'dyte-controlbar').then((elm) => {
    const $dyteControlbarRight = elm.querySelector("#controlbar-right");
    const $dyteControlbarMobile = elm.querySelector("#controlbar-mobile");
    //console.log("$dyteControlbarRight", $dyteControlbarRight);
    if($dyteControlbarRight) {
      const loaderDiv = document.querySelector('.loader');
      console.log(loaderDiv);
      if (showTipButton){
        makeTipButton(elm, $dyteControlbarRight, false);
      }
      const $dyteChatToggle = $dyteControlbarRight.querySelector('dyte-chat-toggle');
      console.log('$dyteChatToggle', $dyteChatToggle)
      waitDyteElm($dyteChatToggle.shadowRoot, 'dyte-controlbar-button').then((controlbarButtonElem) => {
        const buttonElem = controlbarButtonElem.shadowRoot.querySelector('button');
        buttonElem.addEventListener('click', () => {
          setTimeout(()=>{
            detectChat(showTipButton);
          }, 100)
        });
        buttonElem.click();
      })
    } else if($dyteControlbarMobile) {
      $dyteControlbar = elm;
      elm.style.height = "50px";
      if (showTipButton){
        makeTipButton(elm, $dyteControlbarMobile, true);
      }
      detectMobileChat(showTipButton);
    }
  });
}


function checkDyteVideoElem() {
  const $dyteMeeting = document.querySelector("dyte-meeting");

  waitDyteElm($dyteMeeting.shadowRoot, 'dyte-controlbar').then((elm) => {
    const $controlbarMobile = elm.querySelector('#controlbar-mobile');
    const $dyteMoreToggle = elm.querySelector('#controlbar-mobile dyte-more-toggle');
    console.log("<<<<<<<<<<<<<<<<<<<", $dyteMoreToggle);
    waitDyteElm(elm, 'dyte-more-toggle').then((elm) => {
      setTimeout(() => {

        const $dyteChatToggle = elm.querySelector('dyte-chat-toggle');
        if ($dyteChatToggle){
          waitDyteElm($dyteChatToggle.shadowRoot, 'dyte-controlbar-button').then((controlbarButtonElem) => {
            const buttonElem = controlbarButtonElem.shadowRoot.querySelector('button');
            buttonElem.click();
          })
        }
      }, 400)

    })
    if($dyteMoreToggle) {
      waitDyteElm($dyteMoreToggle.shadowRoot, 'dyte-controlbar-button').then((elm) => {
        elm.click();
      })
    }

    waitDyteElm($dyteMeeting.shadowRoot, 'dyte-stage').then((elm) => {
      const $dyteGrid = elm.querySelector('dyte-grid');
      waitDyteElm($dyteGrid.shadowRoot, 'dyte-livestream-player').then((elm) => {
        waitDyteElm(elm.shadowRoot, '.player-container').then((playerContainer) => {
          playerContainer.appendChild(player);
          playerContainer.appendChild(goalShowContainer);
          goalShowContainer.querySelector("#goal-username").innerText = dyteMeeting.meta.meetingTitle;
          window.goalShowContainer = goalShowContainer;

          if(window.goal.status = 1 && typeOfShow >= 2) {
            playerContainer.appendChild(goalContainer);
            goalContainer.querySelector('.epic-goal-progress__tokens').innerText = `$${window.goal_price}`
            let goal_left_p = ((window.goal_current_count/window.goal_price)*100).toFixed(1)
            goal_left_p = Math.min(goal_left_p, 100);
            goalContainer.querySelector('.epic-goal-progress__status--view-cam').innerText = `${goal_left_p}%`
            goalContainer.querySelector('.epic-goal-progress__inner--view-cam').style.height = `${goal_left_p}%`
            goalContainer.classList.remove('hidden')
            window.goalContainer = goalContainer;
            if(!isLivestraemHost) {
              videoEl = playerContainer.querySelector("video");
              checkGoalReached();
            }
          }
        })
      })
      waitDyteElm($dyteGrid.shadowRoot, 'dyte-simple-grid').then((elm) => {
        waitDyteElm(elm.shadowRoot, 'dyte-participant-tile').then((playerContainer) => {
          playerContainer.appendChild(player);
          playerContainer.appendChild(goalShowContainer);
          goalShowContainer.querySelector("#goal-username").innerText = dyteMeeting.meta.meetingTitle;
          window.goalShowContainer = goalShowContainer;

          if(window.goal.status = 1 && typeOfShow >= 2) {
            playerContainer.appendChild(goalContainer);
            goalContainer.querySelector('.epic-goal-progress__tokens').innerText = `$${window.goal_price}`
            let goal_left_p = ((window.goal_current_count / window.goal_price) * 100).toFixed(1)
            goal_left_p = Math.min(goal_left_p, 100);
            goalContainer.querySelector('.epic-goal-progress__status--view-cam').innerText = `${goal_left_p}%`
            goalContainer.querySelector('.epic-goal-progress__inner--view-cam').style.height = `${goal_left_p}%`
            goalContainer.classList.remove('hidden')
            window.goalContainer = goalContainer;
          }
          if (isLivestraemHost) {
            waitDyteElm(playerContainer.shadowRoot, 'video').then((elm) => {
              videoEl = elm
              setTimeout(() => {
                makeThumbnail()
              }, 5000)
            })
          }
        })
      })
    })

    if($controlbarMobile) {
      $dyteControlbar = elm;
      elm.style.position = "absolute";
      elm.style.width = "100%";
      elm.style.bottom = "-50px";
      elm.style.zIndex = "99";
      elm.style['--dyte-colors-background-1000'] = 'transparent';
      elm.style['--dyte-colors-background-800'] = 'transparent';
      const style = document.createElement('style');
      style.textContent = `
          :host { 
            --dyte-colors-background-1000: 'transparent'; 
            --dyte-colors-background-800: 'transparent';
            }
        `;
      elm.shadowRoot.appendChild(style);



      waitDyteElm($dyteMeeting.shadowRoot, 'dyte-stage').then((elm) => {
        const $dyteGrid = elm.querySelector('dyte-grid');
        waitDyteElm($dyteGrid.shadowRoot, 'dyte-simple-grid').then((elm) => {
          console.log(">>>>>>>", elm)
          waitDyteElm(elm.shadowRoot, 'dyte-participant-tile').then((elm) => {
            elm.style.height = "100%";

            const style = document.createElement('style');
            style.textContent = ':host([size="sm"]) { height: 100% !important; top: 0!important;left: 0!important;width: 100%!important;border-radius: 0!important; } :host([size="sm"]) video {border-radius: 0;}';
            elm.shadowRoot.appendChild(style);

          })
          waitDyteElm(elm.shadowRoot, 'dyte-participant-tile').then((elm) => {
            elm.style.height = "100%";

            const style = document.createElement('style');
            style.textContent = ':host([size="sm"]) { height: 100% !important; top: 0!important;left: 0!important;width: 100%!important;border-radius: 0!important; } :host([size="sm"]) video {border-radius: 0;}';
            elm.shadowRoot.appendChild(style);

            const $dyteNameTag = elm.querySelector('dyte-name-tag');
            $dyteNameTag.style.top = "8px";
            $dyteNameTag.style.bottom = "auto";

          })
        })
        waitDyteElm($dyteGrid.shadowRoot, 'dyte-livestream-player').then((elm) => {
          elm.style.margin = '0';
          waitDyteElm(elm.shadowRoot, '.player-container').then((elm) => {
            $video = elm.querySelector('video');
            elm.style.margin = '0';
            elm.style.borderRadius = '0';
            //$video.style.objectFit = 'cover';
            $video.style.borderRadius = '0';
            if(isMobile() && !isLivestraemHost && portrait.matches){
              $video.style.width = 'auto';
            }
            waitDyteElm(elm, '.unmute-popup').then((elm) => {
              elm.style.setProperty('z-index', '99', 'important');
            })
          })
        })
      })
      waitDyteElm($dyteMeeting.shadowRoot, 'dyte-header').then((elm) => {
        elm.style.display = "none";
      })
    }
  })

}

const portrait = window.matchMedia('(orientation: portrait)');

/*portrait.addEventListener('change', (event) => {
  // Check if orientation is portrait
  if (event.matches) {
    const $dyteMeeting = document.querySelector("dyte-meeting");
    console.log('Device orientation is p');
    setTimeout(()=> {
      waitDyteElm($dyteMeeting.shadowRoot, 'dyte-stage[size="md"]').then((elm) => {
        console.log('>>>>>>>>>>>>>>vvv');
        elm.style.backgroundColor = '#e3e3e3';
        const $dyteSidebar = elm.querySelector('dyte-sidebar[size="md"]');
        $dyteSidebar.remove();
        //$dyteSidebar.style.display = 'none';
      })
    }, 200)
  } else {
    console.log('Device orientation is Landscape');
  }
});*/
portrait.addEventListener('change', (event) => {
  // Check if orientation is portrait
  if (event.matches) {
    if($video && isMobile() && !isLivestraemHost){
      $video.style.width = "auto"
    }
    const $dyteMeeting = document.querySelector("dyte-meeting");
    console.log('Device orientation is p');
    setTimeout(()=> {
      waitDyteElm($dyteMeeting.shadowRoot, 'dyte-stage[size="md"]').then((elm) => {
        console.log('>>>>>>>>>>>>>>vvv');
        const $dyteSidebar = elm.querySelector('dyte-sidebar[size="md"]');
        //$dyteSidebar.remove();
        //$dyteSidebar.style.display = 'none';
      })
      waitDyteElm($dyteMeeting.shadowRoot, 'dyte-sidebar[view="full-screen"]').then(($dyteSidebar) => {
        console.log($dyteSidebar)
        console.log('xxxxxxxxxxxxxxxxxxxx');
        //$dyteSidebar.remove();
        $dyteSidebar.style.pointerEvents = 'auto';
        $dyteSidebar.style.visibility = 'visible';
        waitDyteElm($dyteSidebar.shadowRoot, 'dyte-sidebar-ui[view="full-screen"]').then((elm) => {
          const $dyteChat = elm.querySelector('dyte-chat');
          waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-messages-ui-paginated').then((elm) => {
            waitDyteElm(elm.shadowRoot, 'dyte-paginated-list').then((elm) => {
              const $dytePaginatedList = elm.shadowRoot;
              waitDyteElm(elm.shadowRoot, 'dyte-chat-message').then((elm) => {
                [...$dytePaginatedList.querySelectorAll('dyte-chat-message')].forEach((mainElm)=>{
                  waitDyteElm(mainElm.shadowRoot, 'dyte-text-message').then((elm) => {
                    updateChatStyle(mainElm, elm);
                  })
                })
              })
            })
          })
          console.log("$dyteChat", $dyteChat);
        })
      })
    }, 200)
  } else {
    if($video && isMobile() && !isLivestraemHost){
      $video.style.width = "100%"
    }
    const $dyteMeeting = document.querySelector("dyte-meeting");
    setTimeout(()=> {
      waitDyteElm($dyteMeeting.shadowRoot, 'dyte-stage[size="md"]').then((elm) => {
        const $dyteSidebar = elm.querySelector('dyte-sidebar[size="md"]');
        const $dyteChat = $dyteSidebar.shadowRoot.querySelector('dyte-chat');
        waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-messages-ui-paginated').then((elm) => {
          waitDyteElm(elm.shadowRoot, 'dyte-paginated-list').then((elm) => {
            const $dytePaginatedList = elm.shadowRoot;
            waitDyteElm(elm.shadowRoot, 'dyte-chat-message').then((elm) => {
              [...$dytePaginatedList.querySelectorAll('dyte-chat-message')].forEach((mainElm)=>{
                waitDyteElm(mainElm.shadowRoot, 'dyte-text-message').then((elm) => {
                  updateChatStyle(mainElm, elm);
                })
              })
            })
          })
        })
        waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-composer-ui').then((elm) => {
          elm.style.backgroundColor = "transparent";
          waitDyteElm(elm.shadowRoot, '.chat-input').then((elm) => {
            const $chatButtonsLeft = elm.querySelector('.chat-buttons .left');

            if(!isLivestraemHost){
              //$chatButtonsLeft.innerHTML = '';
              makeChatTipButton($chatButtonsLeft);
              makeChatLikeButton($chatButtonsLeft);
            }
          })
        })
      })
      waitDyteElm($dyteMeeting.shadowRoot, 'dyte-sidebar[view="full-screen"]').then(($dyteSidebar) => {
        console.log($dyteSidebar)
        console.log('>>>>>>>>>>>>>>vvv');
        //$dyteSidebar.remove();
        $dyteSidebar.style.pointerEvents = 'none';
        $dyteSidebar.style.visibility = 'hidden';
      })
    }, 200)
    console.log('Device orientation is Landscape');
  }
});


function detectMobileChat(showTipButton) {
  const $dyteMeeting = document.querySelector("dyte-meeting");
  waitDyteElm($dyteMeeting.shadowRoot, 'dyte-stage[size="md"]').then((elm) => {
    console.log('>>>>>>>>>>>>>>vvv');
    //elm.style.backgroundColor = '#e3e3e3';
    const $dyteSidebar = elm.querySelector('dyte-sidebar[size="md"]');
    if($dyteSidebar){
      //$dyteSidebar.remove();
      //$dyteSidebar.style.display = 'none';
    }
  })
  waitDyteElm($dyteMeeting.shadowRoot, 'dyte-sidebar').then((elm) => {
    setTimeout(function (){
      const view = elm.getAttribute('view');
      if(view === 'sidebar'){
        //elm.remove();
        const $dyteSidebar = $dyteMeeting.shadowRoot.querySelector('dyte-sidebar[view="full-screen"]');

        updateSidebarUI($dyteSidebar, showTipButton);

        waitDyteElm($dyteMeeting.shadowRoot, 'dyte-sidebar[size="lg"]').then((elm) => {
          const $dyteChat = elm.shadowRoot.querySelector('dyte-chat');
          waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-messages-ui-paginated').then((elm) => {
            waitDyteElm(elm.shadowRoot, 'dyte-paginated-list').then((elm) => {
              const $dytePaginatedList = elm.shadowRoot;
              waitDyteElm(elm.shadowRoot, 'dyte-chat-message').then((elm) => {
                [...$dytePaginatedList.querySelectorAll('dyte-chat-message')].forEach((mainElm)=>{
                  waitDyteElm(mainElm.shadowRoot, 'dyte-text-message').then((elm) => {
                    updateChatStyle(mainElm, elm);
                  })
                })
              })
            })
          })
        })

        if(!portrait.matches) {
          waitDyteElm(elm.shadowRoot, 'dyte-chat').then(($dyteChat) => {
            waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-messages-ui-paginated').then((elm) => {
              waitDyteElm(elm.shadowRoot, 'dyte-paginated-list').then((elm) => {
                const $dytePaginatedList = elm.shadowRoot;
                waitDyteElm(elm.shadowRoot, 'dyte-chat-message').then((elm) => {
                  [...$dytePaginatedList.querySelectorAll('dyte-chat-message')].forEach((mainElm)=>{
                    waitDyteElm(mainElm.shadowRoot, 'dyte-text-message').then((elm) => {
                      updateChatStyle(mainElm, elm);
                    })
                  })
                })
              })
            })
            waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-composer-ui').then((elm) => {
              elm.style.backgroundColor = "transparent";
              waitDyteElm(elm.shadowRoot, '.chat-input').then((elm) => {
                const $chatButtonsLeft = elm.querySelector('.chat-buttons .left');

                if(showTipButton){
                  //$chatButtonsLeft.innerHTML = '';
                  makeChatTipButton($chatButtonsLeft);
                  makeChatLikeButton($chatButtonsLeft);
                }
              })
            })
          })
          $dyteSidebar.style.pointerEvents = 'none';
          $dyteSidebar.style.visibility = 'hidden';
        }

      } else {
        updateSidebarUI(elm, showTipButton);
      }
      console.log(view)
    }, 100)
  })
}
function detectChat(showTipButton) {
  const $dyteMeeting = document.querySelector("dyte-meeting");
  waitDyteElm($dyteMeeting.shadowRoot, 'dyte-stage', true).then((elm) => {
    waitDyteElm(elm, 'dyte-sidebar').then((elm) => {
      waitDyteElm(elm.shadowRoot, 'dyte-chat').then(($dyteChat) => {
        waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-messages-ui-paginated').then((elm) => {
          waitDyteElm(elm.shadowRoot, 'dyte-paginated-list').then((elm) => {
            const $dytePaginatedList = elm.shadowRoot;
            waitDyteElm(elm.shadowRoot, 'dyte-chat-message').then((elm) => {
              [...$dytePaginatedList.querySelectorAll('dyte-chat-message')].forEach((mainElm)=>{
                waitDyteElm(mainElm.shadowRoot, 'dyte-text-message').then((elm) => {
                  updateChatStyle(mainElm, elm);
                })
              })
            })
          })
        })
        waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-composer-ui').then((elm) => {
          elm.style.backgroundColor = "transparent";
          waitDyteElm(elm.shadowRoot, '.chat-input').then((elm) => {
            const $chatButtonsLeft = elm.querySelector('.chat-buttons .left');

            if(showTipButton){
              //$chatButtonsLeft.innerHTML = '';
              makeChatTipButton($chatButtonsLeft);
              makeChatLikeButton($chatButtonsLeft);
            }
          })
        })
      })
    })
  })
}

function updateSidebarUI(elm, showTipButton) {
  elm.style.backgroundColor = "transparent";
  waitDyteElm(elm.shadowRoot, 'dyte-sidebar-ui[view="full-screen"]').then((elm) => {
    const $dyteChat = elm.querySelector('dyte-chat');
    const $mobileTabs = elm.shadowRoot.querySelector('.mobile-tabs');
    const title = elm.shadowRoot.querySelector('.main-header h3');
    const close = elm.shadowRoot.querySelector('.close');
    const dyteIcon = elm.shadowRoot.querySelector('.close dyte-icon');
    $dyteControlbar.style.bottom = "-50px";
    $mobileTabs.innerHTML = '';
    $mobileTabs.style.margin = "0";
    $mobileTabs.style.height = "50px";
    $mobileTabs.style.border = "none";
    $mobileTabs.style.background = "linear-gradient(180deg,rgba(0,0,0,.4) 0%,rgba(0,0,0,0) 100%)";
    $mobileTabs.style.margin = "0";
    title.style.display = 'none';
    close.style.left = 'initial';
    close.style.top = '7px';
    close.style.right = '50px';
    close.style.zIndex = '9';

    close.addEventListener('click', () => {
      setTimeout(()=>{
        if($dyteControlbar){
          $dyteControlbar.style.bottom = "0";
        }
        detectMobileChat(showTipButton);
      }, 100)
    });

    waitDyteElm(dyteIcon.shadowRoot, '.icon-wrapper').then((elm) => {
      elm.innerHTML = `
         <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 18 19"><path fill="#f8f8f8" fill-rule="evenodd" clip-rule="evenodd" d="M1.70711 0.292893C1.31658 -0.0976311 0.683418 -0.0976311 0.292893 0.292893C-0.0976311 0.683417 -0.0976311 1.31658 0.292893 1.70711L0.958167 2.37238C0.7873 2.57071 0.635898 2.7863 0.506672 3.01631C0.1744 3.60775 -8.45175e-05 4.27491 3.0712e-08 4.95329V17.75C3.0712e-08 18.1545 0.243649 18.5191 0.617331 18.6739C0.991013 18.8287 1.42114 18.7431 1.70713 18.4571L5.35149 14.8125H13.3983L15.2929 16.7071C15.6834 17.0976 16.3166 17.0976 16.7071 16.7071C17.0976 16.3166 17.0976 15.6834 16.7071 15.2929L1.70711 0.292893ZM7.1875 1C6.63522 1 6.1875 1.44772 6.1875 2C6.1875 2.55228 6.63522 3 7.1875 3H13.7971C14.315 3 14.8117 3.20576 15.178 3.57204C15.5442 3.93831 15.75 4.4351 15.75 4.95312V10.8594C15.75 11.021 15.7305 11.1768 15.6943 11.3251C15.5632 11.8616 15.8918 12.4028 16.4283 12.5339C16.9648 12.665 17.506 12.3364 17.6371 11.7999C17.711 11.4976 17.75 11.1824 17.75 10.8594V4.95312C17.75 3.90471 17.3335 2.89923 16.5922 2.15787C15.8509 1.4165 14.8455 1 13.7971 1H7.1875ZM2.25034 3.99592C2.2881 3.9287 2.3297 3.86398 2.37485 3.80204L11.3824 12.8125H4.93725C4.67203 12.8125 4.41766 12.9179 4.23012 13.1054L2 15.3357V4.95296C1.99994 4.61774 2.08616 4.28816 2.25034 3.99592Z"></path></svg>
        `;
    });

    waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-messages-ui-paginated').then((elm) => {
      elm.style.backgroundColor = 'transparent';
      waitDyteElm(elm.shadowRoot, 'dyte-paginated-list').then((elm) => {
        const $dytePaginatedList = elm.shadowRoot;
        elm.style.justifyContent = 'end';
        waitDyteElm(elm.shadowRoot, '.scrollbar').then((elm) => {
          elm.style.width = '75%';
          elm.style.maxWidth = 'var(--dyte-space-96, 384px)';
          elm.style.height = '275px';
          elm.style.flex = 'none';

          const style = document.createElement('style');
          style.textContent = `
                    .empty-list {
                      display: none !important;
                    }
                    `;
          elm.appendChild(style);
        })


        waitDyteElm(elm.shadowRoot, 'dyte-chat-message').then((elm) => {
          [...$dytePaginatedList.querySelectorAll('dyte-chat-message')].forEach((mainElm)=>{
            waitDyteElm(mainElm.shadowRoot, 'dyte-text-message').then((elm) => {
              updateChatStyle(mainElm, elm);
            })
          })
        })


      })
    })


    waitDyteElm($dyteChat.shadowRoot, 'dyte-chat-composer-ui').then((elm) => {
      elm.style.backgroundColor = "transparent";
      waitDyteElm(elm.shadowRoot, '.chat-input').then((elm) => {
        const $textarea = elm.querySelector('textarea');
        const $chatButtonsLeft = elm.querySelector('.chat-buttons .left');
        const $sendButton = elm.querySelector('.chat-buttons .right dyte-tooltip dyte-button');
        const $dyteTooltip = elm.querySelector('.chat-buttons .right dyte-tooltip');
        $sendButton.setAttribute('variant', 'ghost');
        $sendButton.removeAttribute('title');
        elm.style.backgroundColor = 'transparent';
        elm.style.flexDirection = 'row';
        elm.style.border = 'none';

        waitDyteElm($dyteTooltip.shadowRoot, '.tooltip').then((elm) => {
          console.log(elm)
          elm.remove();
        })

        $textarea.setAttribute('placeholder','Add a comment...');

        const style = document.createElement('style');
        style.textContent = `
                    .chat-input {
                      background-color: transparent !important;
                      flex-direction: row !important;
                      border: none !important;
                      margin-bottom: 5px !important;
                      box-sizing: border-box !important;
                      padding-left: 10px;
                    }
                    textarea {
                      --dyte-colors-text-800: 255 255 255;
                      width: 100%;
                      background: rgba(0,0,0,.6) !important;
                      border-radius: 20px !important;
                      border: 2px solid rgba(248,248,248,.2) !important;
                      display: flex;
                      flex: 1;
                      padding: 8px 16px !important;
                      transition: all ease .25s;
                      height: 40px !important;
                      min-height: auto !important;
                      color: #ffffff !important;
                    }
                    .chat-buttons {
                      --dyte-space-2: 2px !important;
                      --tw-bg-opacity: 0 !important;
                    }
                    `;
        elm.appendChild(style);

        console.log($textarea);
        if ($textarea) {
          const backBtn = document.getElementById('backButton');
          backBtn.style.display = 'none';
        }
        if(showTipButton){
          $chatButtonsLeft.innerHTML = '';
          makeChatTipButton($chatButtonsLeft);
          makeChatLikeButton($chatButtonsLeft);
        } else{
          const dyteLivestreamToggle = document.createElement('dyte-livestream-toggle');
          const style = document.createElement('style');
          dyteLivestreamToggle.meeting = dyteMeeting;
          console.log(dyteLivestreamToggle.meeting = dyteMeeting);
          //dyteLivestreamToggle.meeting.livestream.state = "LIVESTREAMING";
          dyteLivestreamToggle.setAttribute('size', 'sm');
          style.textContent = `
                    :host { 
                        --dyte-border-width-md: 0;
                        --dyte-colors-background-1000: transparent;
                        --dyte-space-14: 36px;
                      }
                  `;
          dyteLivestreamToggle.shadowRoot.appendChild(style);

          $chatButtonsLeft.prepend(dyteLivestreamToggle)
        }

      })
    })


  })
}


function updateChatStyle(mainElm, elm) {
  if(elm.querySelector('dyte-text-message .body').innerText.includes('Liked ❤️') ||
      elm.querySelector('dyte-text-message .body').innerText.includes('Liked ❤') ||
      elm.querySelector('dyte-text-message .body').innerText.includes('💰')

  ){
    elm.closest('.message-wrapper').classList.add('message-center')
  }
  if(!isMobile()){
    elm.closest('.message-wrapper').classList.add('is-web')
  }

  const style = document.createElement('style');
  style.textContent = `
                    .avatar {
                        display: none !important;
                    }
                    .message-wrapper {
                       margin-top: 0 !important;
                       margin-left: 10px !important;
                    }
                     
                    .message-wrapper.message-center.is-web {
                      justify-content: center;
                      background-color: #222;
                      border-bottom: 1px solid rgba(59,59,59,.9);
                      padding: 9px 40px 10px;
                    }
                    .message-wrapper.goal {
                      position: relative;
                      background-color: rgba(52, 37, 42, 0.6);
                      border-radius: 4px 16px 16px 4px;
                      padding: 8px 10px;
                      color: #f8f8f8;
                      margin-top: 4px;
                      margin-bottom: 4px;
                    }
                    .message-wrapper.message-center.is-web, 
                    .message-wrapper.goal.is-web {
                        margin: 0;
                        border-radius: 0;
                    }
                    .message-wrapper .message {
                      width: auto;
             
                    }
                    .message-wrapper.message-center .message>div,
                    .message-wrapper.goal .message>div {
                      width: 100%;
                    }
                    .message-wrapper.message-center .message,
                    .message-wrapper.goal .message {
                       flex: 1;
                    }
                    .message-wrapper.goal::before {
                        bottom: 0;
                        content: "";
                        left: 0;
                        pointer-events: none;
                        position: absolute;
                        top: 0;
                        width: 3px;
                        border-radius: 4px 0 0 4px;
                        background-color: #df1162;
                    }
                    
                    dyte-text-message {
                      margin: 0 !important;
                    }
                    
                    
                    
                    .message-wrapper.goal dyte-text-message .head .name {
                      font-size: 12px;
                      line-height: .938rem;
                      color: #f8f8f8;
                      font-weight: 700;
                      display: flex;
                      align-items: center;
                    }
                    
                    .message-wrapper.goal .body.bubble {
                      border-top: 1px solid rgba(248,248,248,.2);
                      border-radius: 0;
                      margin-top: 8px;
                      padding-top: 8px !important;
                      max-width: 100%;
                    }
                    
                    .goal-block-amount {
                      color: #df1162;
                      margin-right: 5px;
                    }
                    
                     .message-wrapper.goal .icon {
                        color: #fff;
                        height: 16px;
                        margin-right: 8px;
                        width: 16px;
                    }
                    
                    .message-wrapper.goal.goal-reached dyte-text-message .head .name {
                      color: #df1162;
                    }
                    
                    .message-wrapper.goal.goal-reached .icon {
                      color: #df1162;
                    }
                    
                    dyte-text-message {
                      font-size: 12px;
                      background-color: transparent;
                      border-radius: 4px 16px 16px 4px;
                      color: var(--dyte-colors-text-1000) !important;
                      font-weight: bold;
                      padding: 4px;
                      margin-left: 10px;
                      margin-top: 0 !important;
                    }
                    dyte-text-message .time {display: none}
                    dyte-text-message .head {
                      margin-left: 0;
                      margin-bottom: 4px;
                    }
                    .message-wrapper.message-center dyte-text-message .head{
                      justify-content: center;
                    }
                    dyte-text-message .body {
                      margin: 0;
                      font-size: 12px !IMPORTANT;
                      line-height: inherit !IMPORTANT;
                      text-align: justify;
                      word-break: break-all;
                      background: transparent !IMPORTANT;
                      padding: 0 !IMPORTANT;
                    }
                    .message-wrapper.message-center dyte-text-message .body{
                      text-align: center;
                      max-width: 100%;
                    }
                    dyte-text-message p {
                      margin: 0;
                    }
                    dyte-text-message .head .name {
                      margin-right: 6px;
                      color: #ffad22;
                    }
                    `;

  elm.removeAttribute('is-continued');
  elm.appendChild(style);

  if(GOAL_REACHED) return;

  const $dyteChatMessage = document.createElement('dyte-chat-message');
  $dyteChatMessage.setAttribute('size', 'sm');


  if(elm.querySelector('dyte-text-message .body').innerText.includes('💰') && typeOfShow >= 2) {
    console.log("type:", typeOfShow);
    console.log("windowgoal_price:", window.goal_price);
    console.log("window_goal_current Nan:", window.goal_current_count);
    let goal_body = `<span class="goal-block-amount">${(window.goal_price - window.goal_current_count).toFixed(0)}</span> 
                <span>left to reach the goal</span>`
    if(window.goal_current_count >= window.goal_price) {
      GOAL_REACHED = true;
      goal_body = `<span>Goal reached!</span>`;

      if(!isLivestraemHost){
        var formdata = new FormData();
        formdata.append("goal_id", window.goal.id);
        formdata.append("Member_id", 1723);

        var requestOptions = {
          method: 'POST',
          body: formdata,
        };

        fetch(domain+"/?mode=Api&job=get_goal_tip_user", requestOptions)
            .then(response => response.text())
            .then((result) => {
              if(result === 'false'){
                videoEl.remove();
                window.goalShowContainer.classList.remove('hidden');
              }
            })
      }


    }

    $dyteChatMessage.shadowRoot.innerHTML = `
    <div class="message-wrapper goal ${isMobile() ? '' : 'is-web'}">
      <div class="message">
        <div>
          <dyte-text-message class="hydrated">
            <div class="head">
              <div class="name">
                <svg class="icon icon-goal" viewBox="0 0 22 22"  >
                    <path d="M11 1C5.477 1 1 5.477 1 11a10 10 0 0 0 20 0c0-1.16-.21-2.31-.61-3.39l-1.6 1.6c.14.59.21 1.19.21 1.79a8 8 0 1 1-8-8c.6 0 1.2.07 1.79.21L14.4 1.6C13.31 1.21 12.16 1 11 1zm7 0l-4 4v1.5l-2.55 2.55C11.3 9 11.15 9 11 9a2 2 0 1 0 2 2c0-.15 0-.3-.05-.45L15.5 8H17l4-4h-3V1zm-7 4a6 6 0 1 0 6 6h-2a4 4 0 1 1-4-4V5z" fill="currentColor"></path>
                 </svg>
                ${goal_body}
              </div>
            </div>
            <div class="body bubble" part="body">
              <div class="text">
                <p><p>Private Show</p></p>
              </div>
            </div>
          </dyte-text-message>
        </div>
      </div>
    </div>`
    mainElm.after($dyteChatMessage)
    $dyteChatMessage.shadowRoot.appendChild(style.cloneNode(true));
  }
}

function updateChatStyle2(elm) {
  elm.removeAttribute('is-continued');
  const style = document.createElement('style');
  style.textContent = `
                    .avatar {
                      display: none !important
                    }
                    dyte-text-message {
                      font-size: 12px;
                      background-color: transparent;
                      border-radius: 4px 16px 16px 4px;
                      color: #f8f8f8 !important;
                      font-weight: bold;
                      padding: 4px;
                      margin-left: 10px;
                      margin-top: 0 !important;
                    }
                    dyte-text-message .time {display: none}
                    dyte-text-message .head {
                      margin-left: 0;
                      margin-bottom: 4px;
                    }
                    dyte-text-message .body {
                      margin: 0;
                      font-size: 12px !IMPORTANT;
                      line-height: inherit !IMPORTANT;
                      text-align: justify;
                      word-break: break-all;
                      background: transparent !IMPORTANT;
                      padding: 0 !IMPORTANT;
                    }
                    dyte-text-message p {
                      margin: 0;
                    }
                    dyte-text-message .head .name {
                      margin-right: 6px;
                      color: #ffad22;
                    }
                    `;
  elm.appendChild(style);
}

function makeChatTipButton($chatButtonsLeft) {
  const $dyteButton = document.createElement('dyte-button');
  $dyteButton.setAttribute('variant', 'ghost');
  $dyteButton.setAttribute('kind', 'icon');

  const $dyteIcon = document.createElement('dyte-icon');
  /* const $iconWrapper = $dyteIcon.querySelector('.icon-wrapper');
	*/
  waitDyteElm($dyteIcon.shadowRoot, '.icon-wrapper').then((elm) => {
    elm.innerHTML = `
         <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/><path fill="currentColor" d="M12 11c-2 0-2-.626-2-1 0-.484.701-1 2-1 1.185 0 1.386.638 1.4 1.018l1-.018h1c0-1.026-.666-2.469-2.4-2.879V6.012h-2v1.073C9.029 7.416 8 8.712 8 10c0 1.12.52 3 4 3 2 0 2 .676 2 1 0 .415-.62 1-2 1-1.841 0-1.989-.857-2-1H8c0 .918.661 2.553 3 2.92V18h2v-1.085c1.971-.331 3-1.627 3-2.915 0-1.12-.52-3-4-3z"/></svg>
        `;
  });

  $dyteButton.appendChild($dyteIcon);
  $chatButtonsLeft.appendChild($dyteButton);
  $dyteButton.addEventListener('click', toggleTipButton);
}

function makeChatLikeButton($chatButtonsLeft) {
  const $dyteButton = document.createElement('dyte-button');
  $dyteButton.setAttribute('variant', 'ghost');
  $dyteButton.setAttribute('kind', 'icon');

  const $dyteIcon = document.createElement('dyte-icon');
  /* const $iconWrapper = $dyteIcon.querySelector('.icon-wrapper');
	*/
  waitDyteElm($dyteIcon.shadowRoot, '.icon-wrapper').then((elm) => {
    const key = `yfl-old` + currentUserId;
    if(localStorage.getItem(key)){
      elm.innerHTML = `
        <svg aria-label="Unlike"    fill="rgb(255, 48, 64)" height="24" role="img" viewBox="0 0 48 48" width="24"><title>Unlike</title><path d="M34.6 3.1c-4.5 0-7.9 1.8-10.6 5.6-2.7-3.7-6.1-5.5-10.6-5.5C6 3.1 0 9.6 0 17.6c0 7.3 5.4 12 10.6 16.5.6.5 1.3 1.1 1.9 1.7l2.3 2c4.4 3.9 6.6 5.9 7.6 6.5.5.3 1.1.5 1.6.5s1.1-.2 1.6-.5c1-.6 2.8-2.2 7.8-6.8l2-1.8c.7-.6 1.3-1.2 2-1.7C42.7 29.6 48 25 48 17.6c0-8-6-14.5-13.4-14.5z"></path></svg>
    `;
    }else {
      elm.innerHTML = `
        <svg aria-label="Like"   height="24" role="img" viewBox="0 0 24 24" width="24"  ><title>Like</title><path fill="currentColor" d="M16.792 3.904A4.989 4.989 0 0 1 21.5 9.122c0 3.072-2.652 4.959-5.197 7.222-2.512 2.243-3.865 3.469-4.303 3.752-.477-.309-2.143-1.823-4.303-3.752C5.141 14.072 2.5 12.167 2.5 9.122a4.989 4.989 0 0 1 4.708-5.218 4.21 4.21 0 0 1 3.675 1.941c.84 1.175.98 1.763 1.12 1.763s.278-.588 1.11-1.766a4.17 4.17 0 0 1 3.679-1.938m0-2a6.04 6.04 0 0 0-4.797 2.127 6.052 6.052 0 0 0-4.787-2.127A6.985 6.985 0 0 0 .5 9.122c0 3.61 2.55 5.827 5.015 7.97.283.246.569.494.853.747l1.027.918a44.998 44.998 0 0 0 3.518 3.018 2 2 0 0 0 2.174 0 45.263 45.263 0 0 0 3.626-3.115l.922-.824c.293-.26.59-.519.885-.774 2.334-2.025 4.98-4.32 4.98-7.94a6.985 6.985 0 0 0-6.708-7.218Z"></path></svg>
        `;
      $dyteButton.addEventListener('click', () => {
        //localStorage.setItem(key, 's');
        toggleLikeButton(elm, key)
      });
    }


  })
  $dyteButton.appendChild($dyteIcon);
  $chatButtonsLeft.appendChild($dyteButton)

}

function toggleLikeButton(elm) {
  const $svg = elm.querySelector('svg');
  const ariaLabel = $svg.getAttribute('aria-label');
  if(ariaLabel === 'Like') {
    dyteMeeting.chat.sendTextMessage('Liked ❤️');
    elm.innerHTML = `
        <svg aria-label="Unlike"    fill="rgb(255, 48, 64)" height="24" role="img" viewBox="0 0 48 48" width="24"><title>Unlike</title><path d="M34.6 3.1c-4.5 0-7.9 1.8-10.6 5.6-2.7-3.7-6.1-5.5-10.6-5.5C6 3.1 0 9.6 0 17.6c0 7.3 5.4 12 10.6 16.5.6.5 1.3 1.1 1.9 1.7l2.3 2c4.4 3.9 6.6 5.9 7.6 6.5.5.3 1.1.5 1.6.5s1.1-.2 1.6-.5c1-.6 2.8-2.2 7.8-6.8l2-1.8c.7-.6 1.3-1.2 2-1.7C42.7 29.6 48 25 48 17.6c0-8-6-14.5-13.4-14.5z"></path></svg>
    `;

    setTimeout(() => {
      elm.innerHTML = `
        <svg aria-label="Like"   height="24" role="img" viewBox="0 0 24 24" width="24"  ><title>Like</title><path fill="currentColor" d="M16.792 3.904A4.989 4.989 0 0 1 21.5 9.122c0 3.072-2.652 4.959-5.197 7.222-2.512 2.243-3.865 3.469-4.303 3.752-.477-.309-2.143-1.823-4.303-3.752C5.141 14.072 2.5 12.167 2.5 9.122a4.989 4.989 0 0 1 4.708-5.218 4.21 4.21 0 0 1 3.675 1.941c.84 1.175.98 1.763 1.12 1.763s.278-.588 1.11-1.766a4.17 4.17 0 0 1 3.679-1.938m0-2a6.04 6.04 0 0 0-4.797 2.127 6.052 6.052 0 0 0-4.787-2.127A6.985 6.985 0 0 0 .5 9.122c0 3.61 2.55 5.827 5.015 7.97.283.246.569.494.853.747l1.027.918a44.998 44.998 0 0 0 3.518 3.018 2 2 0 0 0 2.174 0 45.263 45.263 0 0 0 3.626-3.115l.922-.824c.293-.26.59-.519.885-.774 2.334-2.025 4.98-4.32 4.98-7.94a6.985 6.985 0 0 0-6.708-7.218Z"></path></svg>
    `;
    }, 8000)
  }
  /*else{
    elm.innerHTML = `
        <svg aria-label="Like"   height="24" role="img" viewBox="0 0 24 24" width="24"  ><title>Like</title><path fill="currentColor" d="M16.792 3.904A4.989 4.989 0 0 1 21.5 9.122c0 3.072-2.652 4.959-5.197 7.222-2.512 2.243-3.865 3.469-4.303 3.752-.477-.309-2.143-1.823-4.303-3.752C5.141 14.072 2.5 12.167 2.5 9.122a4.989 4.989 0 0 1 4.708-5.218 4.21 4.21 0 0 1 3.675 1.941c.84 1.175.98 1.763 1.12 1.763s.278-.588 1.11-1.766a4.17 4.17 0 0 1 3.679-1.938m0-2a6.04 6.04 0 0 0-4.797 2.127 6.052 6.052 0 0 0-4.787-2.127A6.985 6.985 0 0 0 .5 9.122c0 3.61 2.55 5.827 5.015 7.97.283.246.569.494.853.747l1.027.918a44.998 44.998 0 0 0 3.518 3.018 2 2 0 0 0 2.174 0 45.263 45.263 0 0 0 3.626-3.115l.922-.824c.293-.26.59-.519.885-.774 2.334-2.025 4.98-4.32 4.98-7.94a6.985 6.985 0 0 0-6.708-7.218Z"></path></svg>
    `;
  }*/
  console.log(ariaLabel)
}

function makeTipButton($dyteControlbar, wrapper, isMobile) {
  const $dyteTripToggle = document.createElement('dyte-trip-toggle');
  const $dyteTipButton = document.createElement('dyte-controlbar-button');

  wrapper.prepend($dyteTripToggle);
  $dyteTripToggle.attachShadow({mode: 'open'});
  $dyteTipButton.setAttribute('size', isMobile ? 'sm' : 'md');
  $dyteTripToggle.shadowRoot.appendChild($dyteTipButton);
  $dyteTipButton.click();
  $dyteTipButton.addEventListener('click', toggleTipButton);

  waitDyteElm($dyteTipButton.shadowRoot, 'button').then((buttonElem) => {
    const $dyteIcon = buttonElem.querySelector('dyte-icon').shadowRoot;
    const $iconWrapper = $dyteIcon.querySelector('.icon-wrapper');

    buttonElem.insertAdjacentHTML('beforeend',`<span class="label" part="label">Send Tip</span>`);
    $iconWrapper.innerHTML = `
         <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/><path fill="currentColor" d="M12 11c-2 0-2-.626-2-1 0-.484.701-1 2-1 1.185 0 1.386.638 1.4 1.018l1-.018h1c0-1.026-.666-2.469-2.4-2.879V6.012h-2v1.073C9.029 7.416 8 8.712 8 10c0 1.12.52 3 4 3 2 0 2 .676 2 1 0 .415-.62 1-2 1-1.841 0-1.989-.857-2-1H8c0 .918.661 2.553 3 2.92V18h2v-1.085c1.971-.331 3-1.627 3-2.915 0-1.12-.52-3-4-3z"/></svg>
        `;
  });

  const $dyteLikeToggle = document.createElement('dyte-like-toggle');
  const $dyteLikeButton = document.createElement('dyte-controlbar-button');

  wrapper.prepend($dyteLikeToggle);
  $dyteLikeToggle.attachShadow({mode: 'open'});
  $dyteLikeButton.setAttribute('size', isMobile ? 'sm' : 'md');
  $dyteLikeToggle.shadowRoot.appendChild($dyteLikeButton);
  $dyteLikeButton.click();
  $dyteLikeButton.addEventListener('click', $dyteLikeButton);

  waitDyteElm($dyteLikeButton.shadowRoot, 'button').then((buttonElem) => {
    const $dyteIcon = buttonElem.querySelector('dyte-icon').shadowRoot;
    const $iconWrapper = $dyteIcon.querySelector('.icon-wrapper');

    buttonElem.insertAdjacentHTML('beforeend',`<span class="label" part="label">Like</span>`);
    $iconWrapper.innerHTML = `
         <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/><path fill="currentColor" d="M12 11c-2 0-2-.626-2-1 0-.484.701-1 2-1 1.185 0 1.386.638 1.4 1.018l1-.018h1c0-1.026-.666-2.469-2.4-2.879V6.012h-2v1.073C9.029 7.416 8 8.712 8 10c0 1.12.52 3 4 3 2 0 2 .676 2 1 0 .415-.62 1-2 1-1.841 0-1.989-.857-2-1H8c0 .918.661 2.553 3 2.92V18h2v-1.085c1.971-.331 3-1.627 3-2.915 0-1.12-.52-3-4-3z"/></svg>
        `;
    const key = `yfl-old` + currentUserId;
    if(localStorage.getItem(key)){
      $iconWrapper.innerHTML = `
        <svg aria-label="Unlike"    fill="rgb(255, 48, 64)" height="24" role="img" viewBox="0 0 48 48" width="24"><title>Unlike</title><path d="M34.6 3.1c-4.5 0-7.9 1.8-10.6 5.6-2.7-3.7-6.1-5.5-10.6-5.5C6 3.1 0 9.6 0 17.6c0 7.3 5.4 12 10.6 16.5.6.5 1.3 1.1 1.9 1.7l2.3 2c4.4 3.9 6.6 5.9 7.6 6.5.5.3 1.1.5 1.6.5s1.1-.2 1.6-.5c1-.6 2.8-2.2 7.8-6.8l2-1.8c.7-.6 1.3-1.2 2-1.7C42.7 29.6 48 25 48 17.6c0-8-6-14.5-13.4-14.5z"></path></svg>
    `;
    }else {
      $iconWrapper.innerHTML = `
        <svg aria-label="Like"   height="24" role="img" viewBox="0 0 24 24" width="24"  ><title>Like</title><path fill="currentColor" d="M16.792 3.904A4.989 4.989 0 0 1 21.5 9.122c0 3.072-2.652 4.959-5.197 7.222-2.512 2.243-3.865 3.469-4.303 3.752-.477-.309-2.143-1.823-4.303-3.752C5.141 14.072 2.5 12.167 2.5 9.122a4.989 4.989 0 0 1 4.708-5.218 4.21 4.21 0 0 1 3.675 1.941c.84 1.175.98 1.763 1.12 1.763s.278-.588 1.11-1.766a4.17 4.17 0 0 1 3.679-1.938m0-2a6.04 6.04 0 0 0-4.797 2.127 6.052 6.052 0 0 0-4.787-2.127A6.985 6.985 0 0 0 .5 9.122c0 3.61 2.55 5.827 5.015 7.97.283.246.569.494.853.747l1.027.918a44.998 44.998 0 0 0 3.518 3.018 2 2 0 0 0 2.174 0 45.263 45.263 0 0 0 3.626-3.115l.922-.824c.293-.26.59-.519.885-.774 2.334-2.025 4.98-4.32 4.98-7.94a6.985 6.985 0 0 0-6.708-7.218Z"></path></svg>
        `;
      $dyteLikeButton.addEventListener('click', () => {
        //localStorage.setItem(key, 's');
        toggleLikeButton($iconWrapper, key)

      });
    }

  });

  if(isMobile) {
    waitDyteElm($dyteControlbar, '#controlbar-right').then((elm) => {
      const $dyteControlbarLeft = $dyteControlbar.querySelector("#controlbar-left");
      const $dyteTripToggle = $dyteControlbarLeft.querySelector("dyte-trip-toggle");
      if($dyteTripToggle) {
        $dyteTripToggle.remove();
      }
      makeTipButton($dyteControlbar, elm, false);
    });
  } else {
    waitDyteElm($dyteControlbar, '#controlbar-mobile').then((elm) => {
      console.log("elm", elm);
      makeTipButton($dyteControlbar, elm, true);
    });
  }
}

setInterval(() => {
  makeThumbnail()
}, 3 * 60 * 1000)

function makeThumbnail() {
  if(!videoEl) return;
  var canvas = document.createElement('canvas');
  var cx = canvas.getContext('2d');
  canvas.style.display = 'none'
  canvas.width = videoEl.videoWidth
  canvas.height = videoEl.videoHeight
  document.body.appendChild(canvas)
  console.log(videoEl);
  cx.drawImage(videoEl, 0, 0, videoEl.videoWidth, videoEl.videoHeight);
  const  thumb = canvas.toDataURL();

  if(thumb.includes('data:image')){
    const formdata = new FormData();
    formdata.append("meeting_id", MEETING_ID);
    formdata.append("thumb", thumb);
    formdata.append("viewer_count", dyteMeeting.livestream.viewerCount);

    const requestOptions = {
      method: 'POST',
      body: formdata,
    };

    fetch(domain+"/?mode=Api&job=add_thumbnail", requestOptions)
  }


}

function setTheme(theme) {
  provideDyteDesignSystem(document.getElementById('my-meeting'), {
    theme: theme,
  });
}

function isMobile() {
  let check = false;
  (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
  return check;
}

const init = async () => {

  let currentTheme = 'dark';
  setTheme(currentTheme);
  document.querySelector('.toggleThemeButton').addEventListener("click", () => {
    if(currentTheme === 'light') {
      currentTheme = 'dark';
    } else {
      currentTheme = 'light'
    }
    document.querySelector('html').setAttribute('data-theme',currentTheme)
    setTheme(currentTheme);
  })

  dyteMeeting = await initializeDyteMeeting();
  currentUserId = dyteMeeting.self.userId;
  isLivestraemHost = dyteMeeting.self.presetName === "livestream_host";

  /*dyteMeeting.chat.on('chatUpdate', ({ message, messages }) => {
    const scrollbar  = document.querySelector("#my-meeting").shadowRoot.querySelector("dyte-sidebar").shadowRoot.querySelector("dyte-chat").shadowRoot.querySelector("dyte-chat-messages-ui-paginated").shadowRoot.querySelector("dyte-paginated-list").shadowRoot.querySelector("div");
    if(scrollbar){
      waitDyteElm(scrollbar, 'dyte-chat-message:not(.hydrated)').then((elm) => {
        waitDyteElm(elm.shadowRoot, 'dyte-text-message').then((elm) => {
          updateChatStyle(elm);
        })
      })
    }
    const key = `yfl-` + message.userId;
    const isLiked = localStorage.getItem(key);

    if(!isLiked && message.message === "Liked ❤️") {
      localStorage.setItem(key, 's');
      player.stop();
      player.play();
    }
  });*/

  playerMoney.addEventListener("complete", () => {
    playerMoney.stop()
  });


  dyteMeeting.chat.on('chatUpdate', ({ message, messages }) => {
    /*if (isMobile()) {
      const scrollbar  = document.querySelector("#my-meeting").shadowRoot.querySelector("dyte-sidebar").shadowRoot.querySelector("dyte-chat").shadowRoot.querySelector("dyte-chat-messages-ui-paginated").shadowRoot.querySelector("dyte-paginated-list").shadowRoot.querySelector("div");
      if(scrollbar){
        waitDyteElm(scrollbar, 'dyte-chat-message:not(.hydrated)').then((elm) => {
          waitDyteElm(elm.shadowRoot, 'dyte-text-message').then((elm) => {
            updateChatStyle(elm);
          })
        })
      }
    }*/
    const scrollbar = document.querySelector("#my-meeting").shadowRoot.querySelector("dyte-sidebar").shadowRoot.querySelector("dyte-chat").shadowRoot.querySelector("dyte-chat-messages-ui-paginated").shadowRoot.querySelector("dyte-paginated-list").shadowRoot.querySelector("div");
    waitDyteElm(scrollbar, 'dyte-chat-message:not(.hydrated)').then((mainElm) => {
      waitDyteElm(mainElm.shadowRoot, 'dyte-text-message').then((elm) => {
        updateChatStyle(mainElm, elm);
      })
    })
    const key = `yfl-old` + message.userId;
    const isLiked = localStorage.getItem(key);

    if(!isLiked && message.message === "Liked ❤️") {
      //localStorage.setItem(key, 's');
      playerHearts.stop();
      playerHearts.play();
    }
    if(message.message.includes('💰')) {
     // if(window.goal.status == 1)
        if(window.goal.status === 0 || window.goal.status === 1){
        const amount = +message.message.match(/\d+/)[0];
        window.goal_current_count = window.goal_current_count + amount;
        let goal_left_p = ((window.goal_current_count/window.goal_price)*100).toFixed(1)
        goal_left_p = Math.min(goal_left_p, 100);
        window.goalContainer.querySelector('.epic-goal-progress__status--view-cam').innerText = `${goal_left_p}%`
        window.goalContainer.querySelector('.epic-goal-progress__inner--view-cam').style.height = `${goal_left_p}%`
      }

      playerMoney.stop();
      playerMoney.play();
    }
  });


  /* setInterval( ()=> {
	 dyteMeeting.chat.sendTextMessage(`💰 Tipped $30 💰User`);
	 window.goal_current_count = window.goal_current_count + 15;
	 let goal_left_p = ((window.goal_current_count/window.goal_price)*100).toFixed(1)
	 goal_left_p = Math.min(goal_left_p, 100);
	 window.goalContainer.querySelector('.epic-goal-progress__status--view-cam').innerText = `${goal_left_p}%`
	 window.goalContainer.querySelector('.epic-goal-progress__inner--view-cam').style.height = `${goal_left_p}%`
   }, 10000)

   setTimeout( ()=> {
	 dyteMeeting.chat.sendTextMessage(`Liked ❤️`);
   }, 15000)*/



  dyteMeeting.self.addListener('roomJoined', () => {

    console.log('roomJoined');
    const backBtn = document.getElementById('backButton');
    backBtn.style.display = 'none';
    setTimeout(() => {
      const options = {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      };
      var origin   = window.location.origin;
      console.log(MEETING_ID);
      fetch(origin+`?mode=Api&job=start_livestreaming_meeting&meeting_id=`+MEETING_ID, options)
          .then(response => response.json())
          .then(response => console.log(response))
      //.catch(err => console.error(err));
      const $dyteMeeting = document.querySelector("dyte-meeting");
      console.log('$dyteMeeting', $dyteMeeting.shadowRoot.querySelector('dyte-controlbar'));
    }, 1000)
  });

  dyteMeeting.participants.pinned.on('participantJoined', (participant) => {
    console.log(`participantJoined`);
    const backBtn = document.getElementById('backButton');
    backBtn.style.display = 'none';
    setTimeout(() => {
      const options = {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      };
      var origin   = window.location.origin;
      console.log(MEETING_ID);
      fetch(origin+`?mode=Api&job=start_livestreaming_meeting&meeting_id=`+MEETING_ID, options)
          .then(response => response.json())
          .then(response => console.log(response))
      //.catch(err => console.error(err));
      const $dyteMeeting = document.querySelector("dyte-meeting");
      console.log('$dyteMeeting', $dyteMeeting.shadowRoot.querySelector('dyte-controlbar'));
    }, 1000)
  });


  dyteMeeting.self.addListener('roomLeft', () => {
    console.log('roomLeft');
    const backBtn = document.getElementById('backButton');
    console.log(backBtn);
    window.history.back();
    backBtn.style.display = 'flex';
    setTimeout(() => {
      const $dyteMeeting = document.querySelector("dyte-meeting");
      console.log('$dyteMeeting', $dyteMeeting.shadowRoot.querySelector('dyte-controlbar'));
    }, 1000)
  });

  dyteMeeting.participants.joined.on('participantLeft', (participant) => {
    console.log(`participantLeftf`);
    const backBtn = document.getElementById('backButton');
    console.log(backBtn);
    window.history.back();
    backBtn.style.display = 'flex';
    setTimeout(() => {
      const $dyteMeeting = document.querySelector("dyte-meeting");
      console.log('$dyteMeeting', $dyteMeeting.shadowRoot.querySelector('dyte-controlbar'));
    }, 1000)
  });


  const showTipButton = dyteMeeting.self.presetName != "livestream_host";

  checkDyteMeetingElem(showTipButton);
  checkDyteVideoElem();

  // Adds meeting to all dyte members
  // (In this case just the element <dyte-meeting />).

  console.log(dyteMeeting);
  /*dyteMeeting.updateUIConfig({
    colors: {
      primary:         '#2160FD',
      secondary:       '#cbcbcb',
      textPrimary:     '#000000',
      videoBackground: '#ffffff'
    }
  });*/
  addDyteMeetingToDyteClassMembers(document, dyteMeeting);
  document.getElementById('tip-username').innerText = dyteMeeting.meta.meetingTitle;


  $(document).on('click', '.tip-button', function (e){
    var amount = $(this).data('amount');
    var name = $(this).data('name');
    sendTip(amount, ` | ${name}`);
  })

  $(document).on('click', '#tip-button-custom', function(){
    var amount = $('.tip-amount__custom-input').val();
    sendTip(amount, '');
  })

  let isSendingTip = false;
  const tipLoader = $("#tip-loader")

  function sendTip(amount, name) {
    if(amount < 1) return;
    if(isSendingTip) return;
    isSendingTip = true;
    tipLoader.addClass('show')
    var formdata = new FormData();
    formdata.append("Entertainer_id", entertainerId);
    formdata.append("Member_id", memberId);
    formdata.append("amount", amount);

    var requestOptions = {
      method: 'POST',
      body: formdata,
    };


    //Modal Tips
    document.getElementById('uniqueModalContainer').innerHTML = `
<div id="uniqueModal" class="modal">
  <div class="modal-content">
  <div class="modal-header">
  <p>Not enough money for tip!</p>
   <button type="button" id="uniqueClose" class="close" data-dismiss="modal" aria-label="Close">
       <span aria-hidden="true">&times;</span>
   </button>
</div>
  <div class="modal-body">
   <p> In order to send a tip, <a href="/?mode=Purchase&job=purchase&type=Tip">
   you need to deposit money into your account </a></p>
</div>
  </div>
</div>
`;

    //Dulicate Modal
    document.getElementById('dublicateModalContainer').innerHTML = `
<div id="dublicateModal" class="modal">
  <div class="modal-content">
  <div class="modal-header">
  <p>Please try to tip other amount!
   <button type="button" id="dublicateClose" class="close" data-dismiss="modal" aria-label="Close">
       <span aria-hidden="true">&times;</span>
   </button>
   </p>
</div>
  <div class="modal-body">
   <p> Please change your TIP amount this time</p>
</div>
  </div>
</div>
`;

    var uniqueModal = document.getElementById("uniqueModal");
    var uniqueClose = document.getElementById("uniqueClose");

    function showUniqueModal() {
      uniqueModal.style.display = "block";
    }
    uniqueClose.onclick = function() {
      uniqueModal.style.display = "none";
    }

    window.onclick = function(event) {
      if (event.target == uniqueModal) {
        uniqueModal.style.display = "none";
      }
    }

// Dulicate error Tips
    var dublicateModal = document.getElementById("dublicateModal");
    var dublicateClose = document.getElementById("dublicateClose");

    function showDublicateModal() {
      dublicateModal.style.display = "block";
    }
    dublicateClose.onclick = function() {
      dublicateModal.style.display = "none";
    }

    window.onclick = function(event) {
      if (event.target == dublicateModal) {
        dublicateModal.style.display = "none";
      }
    }




    fetch(domain+"/?mode=Api&job=live_tip", requestOptions)
        .then(response => response.text())
        .then((result) => {
          isSendingTip =  false;
          tipLoader.removeClass('show')

          const parsedResult = JSON.parse(result);

          console.log('status:', parsedResult.status);
          console.log(requestOptions);
          console.log(parsedResult);
          console.log('response_text: ', parsedResult.response_text);

          if( parsedResult.status === 'Success') {
            console.log('succeess')
            $('#tipModal').modal('hide');
            dyteMeeting.chat.sendTextMessage(`💰 Tipped $${amount} 💰${name}`);
          }
          else if(parsedResult.response_text.startsWith("Duplicate transaction")){
            console.log('Dublicate')
            $('#tipModal').modal('hide');
            showDublicateModal();
          }
          else if(parsedResult.status === 'Cancel') {
            console.log('NO success')
            $('#tipModal').modal('hide');
            showUniqueModal();
            // console.log('NOsucceess')
            // if(amount){
            //  window.location.href = "/?mode=Purchase&job=purchase&type=Tip&amount="+amount;
            // }
            // else{
            //  window.location.href = "/?mode=Purchase&job=purchase&type=Tip";
            // }
          }

        })
        .catch((error) => {
         // tipLoader.removeClass('show')
          $('#tipModal').modal('hide');
           //showUniqueModal();
          if(amount){
           // window.location.href = "/?mode=Purchase&job=purchase&type=Tip&amount="+amount;

          }
          else{
           // window.location.href = "/?mode=Purchase&job=purchase&type=Tip";
          }
        });
  }
};
init();
