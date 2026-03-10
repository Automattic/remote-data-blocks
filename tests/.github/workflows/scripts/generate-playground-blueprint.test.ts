import { describe, expect, it, vi } from 'vitest';

// eslint-disable-next-line @typescript-eslint/no-require-imports
const { run } = require( '../../../../.github/workflows/scripts/generate-playground-blueprint' );

function createMockGithub(
	comments: Array< { id: number; user: { type: string }; body: string } > = []
) {
	return {
		rest: {
			issues: {
				listComments: vi.fn().mockResolvedValue( { data: comments } ),
				createComment: vi.fn().mockResolvedValue( {} ),
				updateComment: vi.fn().mockResolvedValue( {} ),
			},
		},
	};
}

function createMockContext( issueNumber = 42 ) {
	return {
		repo: { owner: 'Automattic', repo: 'remote-data-blocks' },
		issue: { number: issueNumber },
	};
}

describe( 'generate-playground-blueprint', () => {
	it( 'should create a new comment when no existing comment is found', async () => {
		const github = createMockGithub();
		const context = createMockContext( 42 );

		await run( { github, context } );

		expect( github.rest.issues.listComments ).toHaveBeenCalled();
		const listCall = github.rest.issues.listComments.mock.calls[ 0 ][ 0 ];
		expect( listCall.owner ).toBe( 'Automattic' );
		expect( listCall.repo ).toBe( 'remote-data-blocks' );
		expect( listCall.issue_number ).toBe( 42 );
		const createCall = github.rest.issues.createComment.mock.calls[ 0 ][ 0 ];
		expect( createCall.owner ).toBe( 'Automattic' );
		expect( createCall.repo ).toBe( 'remote-data-blocks' );
		expect( createCall.issue_number ).toBe( 42 );
		expect( createCall.body ).toContain( 'Test this PR in' );
		expect( github.rest.issues.updateComment ).not.toHaveBeenCalled();
	} );

	it( 'should update an existing bot comment', async () => {
		const existingComment = {
			id: 999,
			user: { type: 'Bot' },
			body: 'Test this PR in WordPress Playground',
		};
		const github = createMockGithub( [ existingComment ] );
		const context = createMockContext( 42 );

		await run( { github, context } );

		expect( github.rest.issues.updateComment ).toHaveBeenCalledWith( {
			owner: 'Automattic',
			repo: 'remote-data-blocks',
			comment_id: 999,
			body: expect.stringContaining( 'Test this PR in' ),
		} );
		expect( github.rest.issues.createComment ).not.toHaveBeenCalled();
	} );

	it( 'should skip non-bot comments when searching for existing comment', async () => {
		const userComment = {
			id: 888,
			user: { type: 'User' },
			body: 'Test this PR in some other context',
		};
		const github = createMockGithub( [ userComment ] );
		const context = createMockContext();

		await run( { github, context } );

		expect( github.rest.issues.createComment ).toHaveBeenCalled();
		expect( github.rest.issues.updateComment ).not.toHaveBeenCalled();
	} );

	it( 'should skip bot comments that do not contain the marker text', async () => {
		const unrelatedBotComment = {
			id: 777,
			user: { type: 'Bot' },
			body: 'Some other bot comment',
		};
		const github = createMockGithub( [ unrelatedBotComment ] );
		const context = createMockContext();

		await run( { github, context } );

		expect( github.rest.issues.createComment ).toHaveBeenCalled();
		expect( github.rest.issues.updateComment ).not.toHaveBeenCalled();
	} );

	it( 'should use prNumber parameter instead of context.issue.number', async () => {
		const github = createMockGithub();
		const context = createMockContext( 42 );

		await run( { github, context, prNumber: 100 } );

		expect( github.rest.issues.listComments ).toHaveBeenCalledWith(
			expect.objectContaining( { issue_number: 100 } )
		);
		expect( github.rest.issues.createComment ).toHaveBeenCalledWith(
			expect.objectContaining( {
				issue_number: 100,
				body: expect.stringContaining( 'remote-data-blocks-100' ),
			} )
		);
	} );

	it( 'should fall back to context.issue.number when prNumber is not provided', async () => {
		const github = createMockGithub();
		const context = createMockContext( 55 );

		await run( { github, context } );

		expect( github.rest.issues.listComments ).toHaveBeenCalledWith(
			expect.objectContaining( { issue_number: 55 } )
		);
		expect( github.rest.issues.createComment ).toHaveBeenCalledWith(
			expect.objectContaining( {
				issue_number: 55,
				body: expect.stringContaining( 'remote-data-blocks-55' ),
			} )
		);
	} );

	it( 'should include the correct PR number in the playground URL', async () => {
		const github = createMockGithub();
		const context = createMockContext();

		await run( { github, context, prNumber: 123 } );

		const call = github.rest.issues.createComment.mock.calls[ 0 ][ 0 ];
		expect( call.body ).toContain( 'artifact=remote-data-blocks-123' );
		expect( call.body ).toContain( 'pr=123' );
		expect( call.body ).toContain( 'PR#123' );
	} );
} );
