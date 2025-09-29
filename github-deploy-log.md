2025-09-29T18:06:37.1883229Z Current runner version: '2.328.0'
2025-09-29T18:06:37.1914608Z ##[group]Runner Image Provisioner
2025-09-29T18:06:37.1915959Z Hosted Compute Agent
2025-09-29T18:06:37.1916823Z Version: 20250912.392
2025-09-29T18:06:37.1917963Z Commit: d921fda672a98b64f4f82364647e2f10b***67d0b
2025-09-29T18:06:37.1919024Z Build Date: 2025-09-12T15:23:14Z
2025-09-29T18:06:37.1919999Z ##[endgroup]
2025-09-29T18:06:37.1920927Z ##[group]Operating System
2025-09-29T18:06:37.1921968Z Ubuntu
2025-09-29T18:06:37.1922853Z 24.04.3
2025-09-29T18:06:37.1923604Z LTS
2025-09-29T18:06:37.1924468Z ##[endgroup]
2025-09-29T18:06:37.1925279Z ##[group]Runner Image
2025-09-29T18:06:37.1926161Z Image: ubuntu-24.04
2025-09-29T18:06:37.1927070Z Version: 202509***.53.1
2025-09-29T18:06:37.1928471Z Included Software: https://github.com/actions/runner-images/blob/ubuntu24/202509***.53/images/ubuntu/Ubuntu2404-Readme.md
2025-09-29T18:06:37.1930655Z Image Release: https://github.com/actions/runner-images/releases/tag/ubuntu24%2F202509***.53
2025-09-29T18:06:37.1932194Z ##[endgroup]
2025-09-29T18:06:37.1933932Z ##[group]GITHUB_TOKEN Permissions
2025-09-29T18:06:37.1937093Z Contents: read
2025-09-29T18:06:37.1938105Z Metadata: read
2025-09-29T18:06:37.1938944Z Packages: read
2025-09-29T18:06:37.1939789Z ##[endgroup]
2025-09-29T18:06:37.1943176Z Secret source: Actions
2025-09-29T18:06:37.1944260Z Prepare workflow directory
2025-09-29T18:06:37.2321834Z Prepare all required actions
2025-09-29T18:06:37.2384991Z Getting action download info
2025-09-29T18:06:37.7692786Z Download action repository 'actions/checkout@v4' (SHA:08eba0b27e820071cde6df949e0beb9ba4906955)
2025-09-29T18:06:37.9524374Z Download action repository 'actions/setup-node@v4' (SHA:49933ea5288caeca8642d1e84afbd3f7d6820020)
2025-09-29T18:06:38.0603929Z Download action repository 'appleboy/ssh-action@v0.1.10' (SHA:334f9259f2f8eb3376d33fa4c684fff373f2c2a6)
2025-09-29T18:06:38.4969715Z Download action repository 'wlixcc/SFTP-Deploy-Action@v1.2' (SHA:f19d10cf6bed527748f74c35abd43cdc0ebbeb9f)
2025-09-29T18:06:39.1868396Z Complete job name: deploy
2025-09-29T18:06:39.2500424Z ##[group]Build container for action use: '/home/runner/work/_actions/appleboy/ssh-action/v0.1.10/Dockerfile'.
2025-09-29T18:06:39.2560446Z ##[command]/usr/bin/docker build -t d1b88d:30d317877545426aa0d921e75240a457 -f "/home/runner/work/_actions/appleboy/ssh-action/v0.1.10/Dockerfile" "/home/runner/work/_actions/appleboy/ssh-action/v0.1.10"
2025-09-29T18:06:41.9468716Z #0 building with "default" instance using docker driver
2025-09-29T18:06:41.9472932Z 
2025-09-29T18:06:41.9473391Z #1 [internal] load build definition from Dockerfile
2025-09-29T18:06:41.9474255Z #1 transferring dockerfile: 171B done
2025-09-29T18:06:41.9475004Z #1 DONE 0.0s
2025-09-29T18:06:41.9475341Z 
2025-09-29T18:06:41.9475793Z #2 [internal] load metadata for ghcr.io/appleboy/drone-ssh:1.6.13
2025-09-29T18:06:42.6916960Z #2 DONE 0.9s
2025-09-29T18:06:42.8195642Z 
2025-09-29T18:06:42.8197902Z #3 [internal] load .dockerignore
2025-09-29T18:06:42.8200277Z #3 transferring context: 2B done
2025-09-29T18:06:42.8201664Z #3 DONE 0.0s
2025-09-29T18:06:42.8202481Z 
2025-09-29T18:06:42.8202766Z #4 [internal] load build context
2025-09-29T18:06:42.8203688Z #4 transferring context: 108B done
2025-09-29T18:06:42.8204343Z #4 DONE 0.0s
2025-09-29T18:06:42.8204670Z 
2025-09-29T18:06:42.8205415Z #5 [1/3] FROM ghcr.io/appleboy/drone-ssh:1.6.13@sha256:32deffb4c38aaa433065aad5178575fa7651be6e13c757eb57f73d88aec7591d
2025-09-29T18:06:42.8206747Z #5 resolve ghcr.io/appleboy/drone-ssh:1.6.13@sha256:32deffb4c38aaa433065aad5178575fa7651be6e13c757eb57f73d88aec7591d done
2025-09-29T18:06:42.8207672Z #5 sha256:32deffb4c38aaa433065aad5178575fa7651be6e13c757eb57f73d88aec7591d 2.38kB / 2.38kB done
2025-09-29T18:06:42.8208489Z #5 sha256:7c01b1df78e6aa527f41bc1cb005c2fe21df6580c2d2ecb1d776b1b1548a192b 864B / 864B done
2025-09-29T18:06:42.8209299Z #5 sha256:1e003c3cb21c59fdb129456560df9187a39b46132321539002e2540330f20a3e 3.34kB / 3.34kB done
2025-09-29T18:06:42.8210570Z #5 sha256:f56be85fc***e46face30e2c3de3f7fe7c15f8fd7c4e5add29d7f64b87abdaa09 0B / 3.37MB 0.1s
2025-09-29T18:06:42.8212698Z #5 sha256:e1c1ae1efe86967d4421951ec02c13d6a5061e7dc3e653c2d6b6781d6f166d1b 0B / 284.75kB 0.1s
2025-09-29T18:06:42.8213842Z #5 sha256:18402fbc4c4d8ab313c49cf9467678b17eac62b6d12a9481e5e81acd14c87fd9 0B / 2.42MB 0.1s
2025-09-29T18:06:43.1201873Z #5 sha256:e1c1ae1efe86967d4421951ec02c13d6a5061e7dc3e653c2d6b6781d6f166d1b 284.75kB / 284.75kB 0.2s done
2025-09-29T18:06:43.1205177Z #5 sha256:18402fbc4c4d8ab313c49cf9467678b17eac62b6d12a9481e5e81acd14c87fd9 2.42MB / 2.42MB 0.3s done
2025-09-29T18:06:43.2261988Z #5 sha256:f56be85fc***e46face30e2c3de3f7fe7c15f8fd7c4e5add29d7f64b87abdaa09 3.37MB / 3.37MB 0.4s done
2025-09-29T18:06:43.2263617Z #5 extracting sha256:f56be85fc***e46face30e2c3de3f7fe7c15f8fd7c4e5add29d7f64b87abdaa09 0.1s done
2025-09-29T18:06:43.3317671Z #5 extracting sha256:e1c1ae1efe86967d4421951ec02c13d6a5061e7dc3e653c2d6b6781d6f166d1b 0.0s done
2025-09-29T18:06:43.4372131Z #5 extracting sha256:18402fbc4c4d8ab313c49cf9467678b17eac62b6d12a9481e5e81acd14c87fd9
2025-09-29T18:06:43.6788429Z #5 extracting sha256:18402fbc4c4d8ab313c49cf9467678b17eac62b6d12a9481e5e81acd14c87fd9 0.0s done
2025-09-29T18:06:43.6852880Z #5 DONE 0.8s
2025-09-29T18:06:43.6853271Z 
2025-09-29T18:06:43.6853550Z #6 [2/3] COPY entrypoint.sh /entrypoint.sh
2025-09-29T18:06:43.6854183Z #6 DONE 0.0s
2025-09-29T18:06:43.6854490Z 
2025-09-29T18:06:43.6854722Z #7 [3/3] RUN chmod +x /entrypoint.sh
2025-09-29T18:06:43.7305020Z #7 DONE 0.2s
2025-09-29T18:06:43.8848838Z 
2025-09-29T18:06:43.8849526Z #8 exporting to image
2025-09-29T18:06:43.8850183Z #8 exporting layers
2025-09-29T18:06:44.0128033Z #8 exporting layers 0.3s done
2025-09-29T18:06:44.0423962Z #8 writing image sha256:2f8d4a0264a1b4b81ef8bc7be4dc24c8a76b7c514cfa8667757234de7b69ac2a done
2025-09-29T18:06:44.0426777Z #8 naming to docker.io/library/d1b88d:30d317877545426aa0d921e75240a457 done
2025-09-29T18:06:44.0429278Z #8 DONE 0.3s
2025-09-29T18:06:44.0508201Z ##[endgroup]
2025-09-29T18:06:44.0543830Z ##[group]Build container for action use: '/home/runner/work/_actions/wlixcc/SFTP-Deploy-Action/v1.2/Dockerfile'.
2025-09-29T18:06:44.0545379Z ##[command]/usr/bin/docker build -t d1b88d:04fe720f191d44a385a5b1cd1e7d494d -f "/home/runner/work/_actions/wlixcc/SFTP-Deploy-Action/v1.2/Dockerfile" "/home/runner/work/_actions/wlixcc/SFTP-Deploy-Action/v1.2"
2025-09-29T18:06:44.3279646Z #0 building with "default" instance using docker driver
2025-09-29T18:06:44.3282097Z 
2025-09-29T18:06:44.3282518Z #1 [internal] load build definition from Dockerfile
2025-09-29T18:06:44.3283442Z #1 transferring dockerfile: 463B done
2025-09-29T18:06:44.3284224Z #1 DONE 0.0s
2025-09-29T18:06:44.3284734Z 
2025-09-29T18:06:44.3285293Z #2 [internal] load metadata for docker.io/library/alpine:3.10
2025-09-29T18:06:44.5311642Z #2 ...
2025-09-29T18:06:44.5313134Z 
2025-09-29T18:06:44.5313609Z #3 [auth] library/alpine:pull token for registry-1.docker.io
2025-09-29T18:06:44.5314083Z #3 DONE 0.0s
2025-09-29T18:06:44.6806553Z 
2025-09-29T18:06:44.6807955Z #2 [internal] load metadata for docker.io/library/alpine:3.10
2025-09-29T18:06:45.1004264Z #2 DONE 0.9s
2025-09-29T18:06:45.2295989Z 
2025-09-29T18:06:45.2300844Z #4 [internal] load .dockerignore
2025-09-29T18:06:45.2305407Z #4 transferring context: 2B done
2025-09-29T18:06:45.2306039Z #4 DONE 0.0s
2025-09-29T18:06:45.2306350Z 
2025-09-29T18:06:45.2306603Z #5 [internal] load build context
2025-09-29T18:06:45.2307229Z #5 transferring context: 845B done
2025-09-29T18:06:45.2308721Z #5 DONE 0.0s
2025-09-29T18:06:45.2310223Z 
2025-09-29T18:06:45.2312069Z #6 [1/5] FROM docker.io/library/alpine:3.10@sha256:451eee8bedcb2f029756dc3e9d73bab0e7943c1ac55cff3a4861c52a0fdd3e98
2025-09-29T18:06:45.2313653Z #6 resolve docker.io/library/alpine:3.10@sha256:451eee8bedcb2f029756dc3e9d73bab0e7943c1ac55cff3a4861c52a0fdd3e98 done
2025-09-29T18:06:45.2328655Z #6 sha256:396c31837116ac290458afcb928f68b6cc1c7bdd6963fc72f52f365a2a89c1b5 0B / 2.80MB 0.1s
2025-09-29T18:06:45.2330059Z #6 sha256:451eee8bedcb2f029756dc3e9d73bab0e7943c1ac55cff3a4861c52a0fdd3e98 1.64kB / 1.64kB done
2025-09-29T18:06:45.2332238Z #6 sha256:e515aad2ed234a5072c4d2ef86a1cb77d5bfe4b11aa865d9214875734c4eeb3c 528B / 528B done
2025-09-29T18:06:45.2333671Z #6 sha256:e7b300aee9f9bf3433d32bc9305bfdd***183beb59d933b48d77ab56ba53a197a 1.47kB / 1.47kB done
2025-09-29T18:06:45.3564480Z #6 sha256:396c31837116ac290458afcb928f68b6cc1c7bdd6963fc72f52f365a2a89c1b5 2.80MB / 2.80MB 0.2s done
2025-09-29T18:06:45.3565874Z #6 extracting sha256:396c31837116ac290458afcb928f68b6cc1c7bdd6963fc72f52f365a2a89c1b5 0.1s done
2025-09-29T18:06:45.5781942Z #6 DONE 0.3s
2025-09-29T18:06:45.5782310Z 
2025-09-29T18:06:45.5782572Z #7 [2/5] COPY entrypoint.sh /entrypoint.sh
2025-09-29T18:06:45.5783186Z #7 DONE 0.0s
2025-09-29T18:06:45.5783440Z 
2025-09-29T18:06:45.5783672Z #8 [3/5] RUN chmod 777 entrypoint.sh
2025-09-29T18:06:45.5888395Z #8 DONE 0.2s
2025-09-29T18:06:45.7406085Z 
2025-09-29T18:06:45.7406777Z #9 [4/5] RUN apk update
2025-09-29T18:06:45.7893292Z #9 0.199 fetch http://dl-cdn.alpinelinux.org/alpine/v3.10/main/x86_64/APKINDEX.tar.gz
2025-09-29T18:06:45.9923749Z #9 0.402 fetch http://dl-cdn.alpinelinux.org/alpine/v3.10/community/x86_64/APKINDEX.tar.gz
2025-09-29T18:06:46.2164562Z #9 0.626 v3.10.9-43-g3feb769ea3 [http://dl-cdn.alpinelinux.org/alpine/v3.10/main]
2025-09-29T18:06:46.2168784Z #9 0.626 v3.10.6-10-ged79a86de3 [http://dl-cdn.alpinelinux.org/alpine/v3.10/community]
2025-09-29T18:06:46.2175976Z #9 0.626 OK: 10344 distinct packages available
2025-09-29T18:06:46.3902479Z #9 DONE 0.6s
2025-09-29T18:06:46.3903892Z 
2025-09-29T18:06:46.3904168Z #10 [5/5] RUN apk add --no-cache openssh
2025-09-29T18:06:46.4037776Z #10 0.164 fetch http://dl-cdn.alpinelinux.org/alpine/v3.10/main/x86_64/APKINDEX.tar.gz
2025-09-29T18:06:46.5296411Z #10 0.216 fetch http://dl-cdn.alpinelinux.org/alpine/v3.10/community/x86_64/APKINDEX.tar.gz
2025-09-29T18:06:46.5302102Z #10 0.290 (1/9) Installing openssh-keygen (8.1_p1-r0)
2025-09-29T18:06:46.6605071Z #10 0.298 (2/9) Installing ncurses-terminfo-base (6.1_p20190518-r2)
2025-09-29T18:06:46.6606259Z #10 0.307 (3/9) Installing ncurses-libs (6.1_p20190518-r2)
2025-09-29T18:06:46.6606977Z #10 0.315 (4/9) Installing libedit (20190324.3.1-r0)
2025-09-29T18:06:46.6607659Z #10 0.323 (5/9) Installing openssh-client (8.1_p1-r0)
2025-09-29T18:06:46.6608357Z #10 0.348 (6/9) Installing openssh-sftp-server (8.1_p1-r0)
2025-09-29T18:06:46.6608875Z #10 0.352 (7/9) Installing openssh-server-common (8.1_p1-r0)
2025-09-29T18:06:46.6609324Z #10 0.420 (8/9) Installing openssh-server (8.1_p1-r0)
2025-09-29T18:06:46.9017879Z #10 0.436 (9/9) Installing openssh (8.1_p1-r0)
2025-09-29T18:06:46.9018669Z #10 0.442 Executing busybox-1.30.1-r5.trigger
2025-09-29T18:06:46.9019264Z #10 0.449 OK: 11 MiB in 23 packages
2025-09-29T18:06:46.9019599Z #10 DONE 0.5s
2025-09-29T18:06:46.9019782Z 
2025-09-29T18:06:46.9019914Z #11 exporting to image
2025-09-29T18:06:46.9020212Z #11 exporting layers
2025-09-29T18:06:47.0290459Z #11 exporting layers 0.3s done
2025-09-29T18:06:47.0669353Z #11 writing image sha256:f970c364f1c8e69a196777cca3f1ffee9fd13e2a9916f33474a95239c9672d02 done
2025-09-29T18:06:47.0671704Z #11 naming to docker.io/library/d1b88d:04fe720f191d44a385a5b1cd1e7d494d done
2025-09-29T18:06:47.0673761Z #11 DONE 0.3s
2025-09-29T18:06:47.0763584Z ##[endgroup]
2025-09-29T18:06:47.1054143Z ##[group]Run actions/checkout@v4
2025-09-29T18:06:47.1054867Z with:
2025-09-29T18:06:47.1055192Z   repository: gjoeckel/otter
2025-09-29T18:06:47.1055732Z   token: ***
2025-09-29T18:06:47.1056018Z   ssh-strict: true
2025-09-29T18:06:47.1056314Z   ssh-user: git
2025-09-29T18:06:47.1056599Z   persist-credentials: true
2025-09-29T18:06:47.1056920Z   clean: true
2025-09-29T18:06:47.1057211Z   sparse-checkout-cone-mode: true
2025-09-29T18:06:47.1057558Z   fetch-depth: 1
2025-09-29T18:06:47.1057835Z   fetch-tags: false
2025-09-29T18:06:47.1058127Z   show-progress: true
2025-09-29T18:06:47.1058408Z   lfs: false
2025-09-29T18:06:47.1058678Z   submodules: false
2025-09-29T18:06:47.1058964Z   set-safe-directory: true
2025-09-29T18:06:47.1059774Z ##[endgroup]
2025-09-29T18:06:47.2327629Z Syncing repository: gjoeckel/otter
2025-09-29T18:06:47.2329896Z ##[group]Getting Git version info
2025-09-29T18:06:47.2330626Z Working directory is '/home/runner/work/otter/otter'
2025-09-29T18:06:47.2332119Z [command]/usr/bin/git version
2025-09-29T18:06:47.2364005Z git version 2.51.0
2025-09-29T18:06:47.2395265Z ##[endgroup]
2025-09-29T18:06:47.2414577Z Temporarily overriding HOME='/home/runner/work/_temp/a3158080-3048-40df-b1d2-c68656f27b06' before making global git config changes
2025-09-29T18:06:47.2416061Z Adding repository directory to the temporary git global config as a safe directory
2025-09-29T18:06:47.2420564Z [command]/usr/bin/git config --global --add safe.directory /home/runner/work/otter/otter
2025-09-29T18:06:47.2469237Z Deleting the contents of '/home/runner/work/otter/otter'
2025-09-29T18:06:47.2473493Z ##[group]Initializing the repository
2025-09-29T18:06:47.2478809Z [command]/usr/bin/git init /home/runner/work/otter/otter
2025-09-29T18:06:47.2626790Z hint: Using 'master' as the name for the initial branch. This default branch name
2025-09-29T18:06:47.2628216Z hint: is subject to change. To configure the initial branch name to use in all
2025-09-29T18:06:47.2629231Z hint: of your new repositories, which will suppress this warning, call:
2025-09-29T18:06:47.2630425Z hint:
2025-09-29T18:06:47.2631365Z hint: 	git config --global init.defaultBranch <name>
2025-09-29T18:06:47.2632102Z hint:
2025-09-29T18:06:47.2632764Z hint: Names commonly chosen instead of 'master' are 'main', 'trunk' and
2025-09-29T18:06:47.2634024Z hint: 'development'. The just-created branch can be renamed via this command:
2025-09-29T18:06:47.2634855Z hint:
2025-09-29T18:06:47.2635353Z hint: 	git branch -m <name>
2025-09-29T18:06:47.2635886Z hint:
2025-09-29T18:06:47.2636591Z hint: Disable this message with "git config set advice.defaultBranchName false"
2025-09-29T18:06:47.2637605Z Initialized empty Git repository in /home/runner/work/otter/otter/.git/
2025-09-29T18:06:47.2693295Z [command]/usr/bin/git remote add origin https://github.com/gjoeckel/otter
2025-09-29T18:06:47.2723481Z ##[endgroup]
2025-09-29T18:06:47.2724865Z ##[group]Disabling automatic garbage collection
2025-09-29T18:06:47.2728955Z [command]/usr/bin/git config --local gc.auto 0
2025-09-29T18:06:47.2770649Z ##[endgroup]
2025-09-29T18:06:47.2771758Z ##[group]Setting up auth
2025-09-29T18:06:47.2779359Z [command]/usr/bin/git config --local --name-only --get-regexp core\.sshCommand
2025-09-29T18:06:47.2820082Z [command]/usr/bin/git submodule foreach --recursive sh -c "git config --local --name-only --get-regexp 'core\.sshCommand' && git config --local --unset-all 'core.sshCommand' || :"
2025-09-29T18:06:47.3283074Z [command]/usr/bin/git config --local --name-only --get-regexp http\.https\:\/\/github\.com\/\.extraheader
2025-09-29T18:06:47.3331046Z [command]/usr/bin/git submodule foreach --recursive sh -c "git config --local --name-only --get-regexp 'http\.https\:\/\/github\.com\/\.extraheader' && git config --local --unset-all 'http.https://github.com/.extraheader' || :"
2025-09-29T18:06:47.3633081Z [command]/usr/bin/git config --local http.https://github.com/.extraheader AUTHORIZATION: basic ***
2025-09-29T18:06:47.3672336Z ##[endgroup]
2025-09-29T18:06:47.3674831Z ##[group]Fetching the repository
2025-09-29T18:06:47.3678893Z [command]/usr/bin/git -c protocol.version=2 fetch --no-tags --prune --no-recurse-submodules --depth=1 origin +53***8d41254e70ae6e52e6e12d54deece009c43a:refs/remotes/origin/master
2025-09-29T18:06:48.1102403Z From https://github.com/gjoeckel/otter
2025-09-29T18:06:48.1103650Z  * [new ref]         53***8d41254e70ae6e52e6e12d54deece009c43a -> origin/master
2025-09-29T18:06:48.1146894Z ##[endgroup]
2025-09-29T18:06:48.1148077Z ##[group]Determining the checkout info
2025-09-29T18:06:48.1150145Z ##[endgroup]
2025-09-29T18:06:48.1160220Z [command]/usr/bin/git sparse-checkout disable
2025-09-29T18:06:48.1225831Z [command]/usr/bin/git config --local --unset-all extensions.worktreeConfig
2025-09-29T18:06:48.1280987Z ##[group]Checking out the ref
2025-09-29T18:06:48.1300123Z [command]/usr/bin/git checkout --progress --force -B master refs/remotes/origin/master
2025-09-29T18:06:48.1606456Z Reset branch 'master'
2025-09-29T18:06:48.1610230Z branch 'master' set up to track 'origin/master'.
2025-09-29T18:06:48.1617129Z ##[endgroup]
2025-09-29T18:06:48.1660091Z [command]/usr/bin/git log -1 --format=%H
2025-09-29T18:06:48.1685281Z 53***8d41254e70ae6e52e6e12d54deece009c43a
2025-09-29T18:06:48.1943204Z ##[group]Run actions/setup-node@v4
2025-09-29T18:06:48.1943605Z with:
2025-09-29T18:06:48.1943877Z   node-version: 20
2025-09-29T18:06:48.1944172Z   always-auth: false
2025-09-29T18:06:48.1944469Z   check-latest: false
2025-09-29T18:06:48.1944884Z   token: ***
2025-09-29T18:06:48.1945166Z ##[endgroup]
2025-09-29T18:06:48.4083333Z Found in cache @ /opt/hostedtoolcache/node/20.19.5/x64
2025-09-29T18:06:48.4092352Z ##[group]Environment details
2025-09-29T18:06:48.9163534Z node: v20.19.5
2025-09-29T18:06:48.9163875Z npm: 10.8.2
2025-09-29T18:06:48.9164300Z yarn: 1.***.***
2025-09-29T18:06:48.9169538Z ##[endgroup]
2025-09-29T18:06:48.9318421Z ##[group]Run npm ci || npm i
2025-09-29T18:06:48.9318873Z [36;1mnpm ci || npm i[0m
2025-09-29T18:06:48.9319539Z [36;1mnpx esbuild reports/js/reports-entry.js --bundle --format=esm --minify --outfile=reports/dist/reports.bundle.js --log-level=info[0m
2025-09-29T18:06:48.9377554Z shell: /usr/bin/bash -e {0}
2025-09-29T18:06:48.9377987Z ##[endgroup]
2025-09-29T18:06:53.1307504Z 
2025-09-29T18:06:53.1319450Z added 2 packages, and audited 3 packages in 4s
2025-09-29T18:06:53.1320552Z 
2025-09-29T18:06:53.1321418Z found 0 vulnerabilities
2025-09-29T18:06:54.0413431Z npm warn exec The following package was not found and will be installed: esbuild@0.25.10
2025-09-29T18:06:57.1604018Z ✘ [ERROR] Could not resolve "reports/js/reports-entry.js"
2025-09-29T18:06:57.1604859Z 
2025-09-29T18:06:57.1620671Z 1 error
2025-09-29T18:06:57.1784496Z ##[error]Process completed with exit code 1.
2025-09-29T18:06:57.1896367Z Post job cleanup.
2025-09-29T18:06:57.2909168Z [command]/usr/bin/git version
2025-09-29T18:06:57.2951895Z git version 2.51.0
2025-09-29T18:06:57.3012190Z Temporarily overriding HOME='/home/runner/work/_temp/134c7a94-5949-4b0d-b8fe-ce9210bf0459' before making global git config changes
2025-09-29T18:06:57.3016381Z Adding repository directory to the temporary git global config as a safe directory
2025-09-29T18:06:57.3019361Z [command]/usr/bin/git config --global --add safe.directory /home/runner/work/otter/otter
2025-09-29T18:06:57.3060010Z [command]/usr/bin/git config --local --name-only --get-regexp core\.sshCommand
2025-09-29T18:06:57.3115989Z [command]/usr/bin/git submodule foreach --recursive sh -c "git config --local --name-only --get-regexp 'core\.sshCommand' && git config --local --unset-all 'core.sshCommand' || :"
2025-09-29T18:06:57.3398950Z [command]/usr/bin/git config --local --name-only --get-regexp http\.https\:\/\/github\.com\/\.extraheader
2025-09-29T18:06:57.3425448Z http.https://github.com/.extraheader
2025-09-29T18:06:57.3443112Z [command]/usr/bin/git config --local --unset-all http.https://github.com/.extraheader
2025-09-29T18:06:57.3483491Z [command]/usr/bin/git submodule foreach --recursive sh -c "git config --local --name-only --get-regexp 'http\.https\:\/\/github\.com\/\.extraheader' && git config --local --unset-all 'http.https://github.com/.extraheader' || :"
2025-09-29T18:06:57.3850973Z Cleaning up orphan processes