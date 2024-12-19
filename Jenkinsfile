pipeline {
    //agent any
    agent { 
        // # JENKINS AGENT (BUILD SERVER)
        label 'Jenkins-Agent-1_QL_DEV-Server'
    }
    options {
        timeout(time: 45, unit: 'MINUTES')
        //disableConcurrentBuilds()
    }
    //tools {
    //    sonarqube scanner 'SonarScanner_V6.2.1.4610'
    //}
    environment {
        //------------------------------------------------
        PR_TARGET_BRANCH = "${CHANGE_TARGET}"
        PR_SOURCE_BRANCH = "${CHANGE_BRANCH}"
        //------------------------------------------------
        WS_DIR = "/jenkins-pipelines/${JOB_NAME}"
        JENKINS_JOB_NAME = "${env.JOB_NAME.split('/')[1]}"
        SEND_MSTEAMS_NOTIFICATIONS = '0'        
        //------------------------------------------------
    }
    stages {
        stage('PreStage - Initialize WorkSpace & SCM') {
            environment {
                REPO_NAME = sh(script: "basename -s .git ${env.GIT_URL}", returnStdout: true).trim()
            }
            steps {
                script {
                    def allowedBranches = ['main', 'master', 'staging', 'stage']
                    //def allowedBranches = ['main', 'master', 'staging', 'stage', 'development', 'develop']
                    // Validate Job Name
                    if (env.JENKINS_JOB_NAME != "${REPO_NAME}") {
                        error "Job Name (${env.JENKINS_JOB_NAME}) does not match Repository Name (${REPO_NAME})!"
                    }                    
                    // Validate the target branch
                    if (!allowedBranches.contains(PR_TARGET_BRANCH)) {
                        error "Branch '${PR_TARGET_BRANCH}' is not allowed. Exiting the pipeline."
                    } 
                    // Checkout the source code
                    sh "mkdir -p ${WS_DIR} || true"
                    echo "Checking out branch: ${PR_TARGET_BRANCH} from ${PR_SOURCE_BRANCH}"
                    dir("${WS_DIR}") {
                        checkout scm
                    }
                }
            }
        }
/*        stage("OWASP Dependency Check Analysis") {
            environment {
                REPO_NAME = sh(script: "basename -s .git ${env.GIT_URL}", returnStdout: true).trim()
                DEP_CHECK_IMAGE = "owasp/dependency-check:latest"
                DEP_CHECK_DATA_DIR = "/data/security-tools_data/owasp/dependency-check/data"
            }
            steps {
                script {
                    dir ("${WS_DIR}") {
                        sh """
                        mkdir -p ${WS_DIR}/dependency-check-reports
                        docker run --rm --name owasp-dependency-check_${UUID.randomUUID().toString().take(8)} \
                            -v ${DEP_CHECK_DATA_DIR}:/usr/share/dependency-check/data \
                            -v ${WS_DIR}:/src \
                            -v ${WS_DIR}/dependency-check-reports:/report \
                            -v ${DEP_CHECK_DATA_DIR}/mysql-connector-j-9.1.0.jar:/usr/share/dependency-check/lib/mysql-connector-j-9.1.0.jar \
                            ${DEP_CHECK_IMAGE} \
                            --scan /src \
                            --format ALL \
                            --out /report \
                            --project "${REPO_NAME}" \
                            --dbDriverPath "/usr/share/dependency-check/lib/mysql-connector-j-9.1.0.jar" \
                            --propertyfile "/usr/share/dependency-check/data/dependency-check_client.properties" \
                            --noupdate \
                            --prettyPrint
                        """
                    }
                    sh "exit 0"
                }
            }
        }
*/        stage("SonarQube Analysis") { 
            environment {
                REPO_NAME = sh(script: "basename -s .git ${env.GIT_URL}", returnStdout: true).trim()
                SONARSCANNER_IMAGE = "sonarsource/sonar-scanner-cli:latest"
                SONARSCANNER_DATA_DIR = "/data/security-tools_data/sonar/sonarscanner-cli/.sonar"
            }
            steps { 
                script {
                    dir ("${WS_DIR}") {
                        withSonarQubeEnv('QL-SONARQUBE-SERVER') {
                            //REPO_NAME = sh(script: "basename -s .git ${env.GIT_URL}", returnStdout: true).trim()
                            //sh "echo ${REPO_NAME}"
                            withCredentials([
                                string(credentialsId: 'SONARQUBE_SERVER_URL_QL', variable: 'SONARQUBE_SERVER_URL'), 
                                string(credentialsId: 'SONAR-TOKEN_ql-jenkins', variable: 'SONARQUBE_TOKEN')
                            ]) {
                                sh """
                                set -x
                                sudo mkdir -p ${WS_DIR}/.scannerwork && sudo chmod 777 -R ${WS_DIR}/.scannerwork
                                sudo rm -rf ${WS_DIR}/sonarscan-output.log || true
                                docker run --rm --name sonar-scanner_${UUID.randomUUID().toString().take(8)} \
                                    -v ${WS_DIR}:/usr/src \
                                    -v ${SONARSCANNER_DATA_DIR}/.sonar:/opt/sonar-scanner/.sonar \
                                    -v ${WS_DIR}/.scannerwork:/usr/src/.scannerwork \
                                    ${SONAR_SCANNER_IMAGE} \
                                    sh -c "mkdir -p /usr/src/.scannerwork && \
                                    sonar-scanner -X \
                                    -Dsonar.java.binaries=build/classes/java/ \
                                    -Dsonar.projectKey=${REPO_NAME} \
                                    -Dsonar.host.url=$SONAR_SERVER_URL \
                                    -Dsonar.login=$SONARQUBE_TOKEN \
                                    -Dsonar.sources=/usr/src \
                                    -Dsonar.working.directory=/usr/src/.scannerwork \
                                    -Dsonar.dependencyCheck.jsonReportPath=/usr/src/dependency-check-reports/dependency-check-report.json \
                                    -Dsonar.dependencyCheck.xmlReportPath=/usr/src/dependency-check-reports/dependency-check-report.xml \
                                    -Dsonar.dependencyCheck.htmlReportPath=/usr/src/dependency-check-reports/dependency-check-report.html \
                                    -Dsonar.exclusions=**/.scannerwork/**,**/dependency-check-reports/**,**/node_modules/**,**/dist/**,**/build/** \
                                    -Dsonar.verbose=true > /usr/src/sonarscan-output.log"
                                sudo chmod 777 ${WS_DIR}/sonarscan-output.log
                            """
                            echo "Sonar Scanning successful. You can find the scan log at : ${WS_DIR}/sonarscan-output.log"
                            }
                        }
                    }
                }
            } 
        }
        stage("SonarQube Quality Gate") {
            steps {
                //ansiColor('xterm') {
                withCredentials([
                    string(credentialsId: 'SONAR-TOKEN_ql-jenkins', variable: 'SONARQUBE_TOKEN')
                ]) {
                    script {
                        //timeout(time: 1, unit: 'HOURS') {
                        timeout(time: 10, unit: 'MINUTES') {
                            def taskId = sh(script: "grep -oP 'task\\?id=\\K[^ ]+' ${WS_DIR}/sonarscan-output.log", returnStdout: true).trim()
                            echo "Task ID: ${taskId}"
                            def taskUrl = "${SONAR_SERVER_URL}/api/ce/task?id=${taskId}"
                            echo "Task URL: ${taskUrl}"
                            //def jsonSlurper = new groovy.json.JsonSlurper().parseText(response)
                            //def parsedResponse = jsonSlurper.parseText(response)
                            //def status = parsedResponse.task.status
                            //echo "Status: ${status}"
                            def taskStatus = ''
                            while (taskStatus != "SUCCESS" && taskStatus != "FAILED") {
                                sleep 30 // Wait for <> seconds before polling
                                def response = sh(script: "curl -s -u $SONARQUBE_TOKEN: ${taskUrl}", returnStdout: true).trim()
                                echo "SonarQube API Response: ${response}"
                                def json = readJSON text: response
                                taskStatus = json?.task?.status ?: 'PENDING'
                                echo "Current Task Status: ${taskStatus}"
                                if (taskStatus == "CANCELED" || taskStatus == "FAILED") {
                                    error "SonarQube task did not complete successfully. Status: ${taskStatus}"
                                }
                            }
                            if (taskStatus == "SUCCESS") {
                                echo "SonarQube analysis completed successfully."
                            }
                        }
                    }
                }
            }
        }
    }
    post {
        // # Executes below commands in JENKINS AGENT (DEPLOYMENT SERVER) if build success
        success {
            /// Define allowed branches in a script block
            script {
                withCredentials([                    
                    string(credentialsId: 'WEBHOOK_MSTEAMS_CICD_CHANNEL', variable: 'WEBHOOK_MSTEAMS_CICD_CHANNEL'),
                    string(credentialsId: 'MSTEAMS_NOTIFICATION_CICD_PR_COLOUR_CODE_SUCCESS', variable: 'MSTEAMS_NOTIFICATION_CICD_PR_COLOUR_CODE_SUCCESS')
                ]) {
                    def allowedBranches = ['main', 'master', 'staging', 'stage']
                    //def allowedBranches = ['main', 'master', 'staging', 'stage', 'development', 'develop']
                    echo "Checking target branch: ${PR_TARGET_BRANCH}"
                    echo "Allowed branches: ${allowedBranches}"
                    // Only send notification if the branch is allowed
                    if (allowedBranches.contains(PR_TARGET_BRANCH)) {
                        if ("${SEND_MSTEAMS_NOTIFICATIONS}" == '1') {
                            office365ConnectorSend(
                                webhookUrl: "$WEBHOOK_MSTEAMS_CICD_CHANNEL",
                                color: "$MSTEAMS_NOTIFICATION_CICD_PR_COLOUR_CODE_SUCCESS",
                                status: "${currentBuild.currentResult}",
                                message: "SonarQube analysis for ${env.JOB_NAME} passed successfully." 
                            )
                        } else {
                            echo "MS-TEAMS notification is disabled, Notification on SUCCESS is not sent to MS-Teams (CICD Notifications Channel)"
                        }
                    } else {
                        echo "Skipping Teams notification due to branch mismatch."
                    }
                }
            }
        }
        // # Executes below commands in JENKINS AGENT (DEPLOYMENT SERVER) if build success
        failure {
            // # Clean Workspace inside Jenkins Agent () if build fails.
            script {
                withCredentials([                    
                    string(credentialsId: 'WEBHOOK_MSTEAMS_CICD_CHANNEL', variable: 'WEBHOOK_MSTEAMS_CICD_CHANNEL'),
                    string(credentialsId: 'MSTEAMS_NOTIFICATION_CICD_PR_COLOUR_CODE_FAILURE', variable: 'MSTEAMS_NOTIFICATION_CICD_PR_COLOUR_CODE_FAILURE')
                ]) {
                    // Define allowed branches in a script block
                    def allowedBranches = ['main', 'master', 'staging', 'stage']
                    //def allowedBranches = ['main', 'master', 'staging', 'stage', 'development', 'develop']
                    echo "Checking target branch: ${PR_TARGET_BRANCH}"
                    echo "Allowed branches: ${BRANCHES}"
                    // Only send failure notification if the branch is allowed
                    if (allowedBranches.contains(PR_TARGET_BRANCH)) {
                        if ("${SEND_MSTEAMS_NOTIFICATIONS}" == '1') {
                            office365ConnectorSend(
                                webhookUrl: "$WEBHOOK_MSTEAMS_CICD_CHANNEL",
                                color: "$MSTEAMS_NOTIFICATION_CICD_PR_COLOUR_CODE_FAILURE",
                                status: "${currentBuild.currentResult}",
                                message: "SonarQube analysis for ${env.JOB_NAME} failed. Check the logs here: <${env.BUILD_URL}|Open>"
                            )
                        } else {
                            echo "MS-TEAMS notification is disabled, Notification on FAILURE is not sent to MS-Teams (CICD Notifications Channel)"
                        }
                    } else {
                        echo "Skipping Teams notification due to branch mismatch."
                    }
                }
            }
        }
        // # Executes below commands in JENKINS AGENT (DEPLOYMENT SERVER) always.
        always {
            // # Clean Workspace inside Jenkins Agent () if build fails.
            cleanWs()
            script {
                sh "rm -rf ${WS_DIR}"
            }
        } 
    }
}
