(async function () {
    const { execSync } = require('child_process')
    const fs = require('fs')
    const prompt = require('prompt')

    // read package.json
    // get version
    const currentVersion = execSync('git describe --tags --abbrev=0').toString()

    // prompt user for version
    prompt.start()
    const { version } = await prompt.get({
        properties: {
            version: {
                description: 'Enter the version to publish:',
                default: currentVersion
            }
        }
    })

    const json = JSON.parse(fs.readFileSync('package.json').toString())

    json.version = version

    fs.writeFileSync('./package.json', JSON.stringify(json, null, 2))

    const tagVersion = `v${version}`

    execSync('git add .')
    console.log('Files staged.')

    execSync(`git commit -m "Release ${tagVersion}"`)
    console.log('Files committed.')

    execSync(`git tag ${tagVersion}`)
    console.log('Tag version added.')

    execSync(`git push origin ${tagVersion}`)
    console.log('Push complete.')
}())
