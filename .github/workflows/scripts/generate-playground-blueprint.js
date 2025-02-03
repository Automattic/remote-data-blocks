const https = require( 'https' );

const generateWordpressPlaygroundBlueprint = ( runId, prNumber ) => {
	const defaultSchema = {
		meta: {
			title: 'Remote Data Blocks (PR)',
			description: 'Installs a remote-data-blocks plugin PR to WordPress Playground',
			author: 'WordPress VIP',
			categories: [ 'Content' ],
		},

		features: {
			networking: true,
		},

		landingPage: '/wp-admin/admin.php?page=remote-data-blocks-settings',

		login: true,

		preferredVersions: {
			php: '8.2',
			wp: 'latest',
		},

		steps: [
			{
				step: 'setSiteOptions',
				options: {
					blogname: 'Remote Data Blocks (PR)',
					blogdescription: 'Explore the Remote Data Blocks plugin in a WordPress Playground',
				},
			},
			{
				step: 'defineWpConfigConsts',
				consts: {
					USE_PLAYGROUND_CORS_PROXY: true,
				},
			},
			{
				step: 'installPlugin',
				pluginData: {
					caption: 'Installing Remote Data Blocks',
					resource: 'url',
					url: `https://playground.wordpress.net/plugin-proxy.php?org=Automattic&repo=remote-data-blocks&workflow=Build%20Live%20Branch&artifact=remote-data-blocks-${ runId }&pr=${ prNumber }`,
				},
				options: {
					activate: true,
					targetFolderName: 'remote-data-blocks',
				},
			},
		],
	};

	return defaultSchema;
};

async function run( { github, context, core } ) {
	const commentInfo = {
		owner: context.repo.owner,
		repo: context.repo.repo,
		issue_number: context.issue.number,
	};

	const comments = ( await github.rest.issues.listComments( commentInfo ) )
		.data;
	let existingCommentId = null;

	for ( const currentComment of comments ) {
		if (
			currentComment.user.type === 'Bot' &&
			currentComment.body.includes( 'Test using WordPress Playground' )
		) {
			existingCommentId = currentComment.id;
			break;
		}
	}

	const defaultSchema = generateWordpressPlaygroundBlueprint(
		context.runId,
		context.issue.number
	);

	const url = `https://playground.wordpress.net/#${ JSON.stringify(
		defaultSchema
	) }`;

	const body = `
## Test using WordPress Playground
The changes in this pull request can be previewed and tested using a [WordPress Playground](https://developer.wordpress.org/playground/) instance.
[WordPress Playground](https://developer.wordpress.org/playground/) is an experimental project that creates a full WordPress instance entirely within the browser.

[Test this pull request with WordPress Playground](${ url }).

Note that this URL is valid for 30 days from when this comment was last updated. You can update it by closing/reopening the PR or pushing a new commit.
`;

	if ( existingCommentId ) {
		await github.rest.issues.updateComment( {
			owner: commentInfo.owner,
			repo: commentInfo.repo,
			comment_id: existingCommentId,
			body: body,
		} );
	} else {
		commentInfo.body = body;
		await github.rest.issues.createComment( commentInfo );
	}
}

module.exports = { run };
