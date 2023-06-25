// script.mjs
import fs from 'fs';
import prompts from 'prompts';
import gitP from 'simple-git/promise';

const git = gitP();

(async function() {
    const currentVersion = JSON.parse(fs.readFileSync('./package.json')).version;

    const response = await prompts({
        type: 'text',
        name: 'version',
        message: 'Enter the version to publish',
        initial: currentVersion
    });

    const version = response.version;

    const packageJson = JSON.parse(fs.readFileSync('package.json'));
    packageJson.version = version;
    fs.writeFileSync('./package.json', JSON.stringify(packageJson, null, 2));

    const tagVersion = `v${version}`;

    await git.add('./*');
    console.log('Files staged.');

    await git.commit(`Release ${tagVersion}`);
    console.log('Files committed.');

    try {
        await git.addTag(tagVersion);
        console.log('Tag version added.');
    } catch (err) {
        console.log('No previous tags found. New tag created.');
    }

    await git.push('origin', tagVersion);
    console.log('Push complete.');
})();