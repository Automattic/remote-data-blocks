async function run( { github, context } ) {
	const commentInfo = {
		owner: context.repo.owner,
		repo: context.repo.repo,
		issue_number: context.issue.number,
	};

	const comments = ( await github.rest.issues.listComments( commentInfo ) ).data;
	let existingCommentId = null;

	for ( const currentComment of comments ) {
		if ( currentComment.user.type === 'Bot' && currentComment.body.includes( 'Test this PR in' ) ) {
			existingCommentId = currentComment.id;
			break;
		}
	}

	const body = `Test this PR in [WordPress Playground](https://wordpress-playground.atomicsites.blog/#{"landingPage":"/wp-admin/admin.php?page=remote-data-blocks-settings","features":{"networking":true},"login":true,"preferredVersions":{"php":"8.2","wp":"latest"},"steps":[{"step":"setSiteOptions","options":{"blogname":"Remote%20Data%20Blocks%20PR#${ context.issue.number }","blogdescription":"Explore%20the%20Remote%20Data%20Blocks%20plugin%20in%20a%20WordPress%20Playground"}},{"step":"installPlugin","pluginData":{"caption":"Installing%20RDB","resource":"url","url":"https://wordpress-playground.atomicsites.blog/plugin-proxy.php?org=Automattic&repo=remote-data-blocks&workflow=Build%20Live%20Branch&artifact=remote-data-blocks-${ context.issue.number }&pr=${ context.issue.number }"},"options":{"activate":true,"targetFolderName":"remote-data-blocks"}}]}).`;

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
