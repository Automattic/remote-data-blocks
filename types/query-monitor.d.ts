interface RemoteDataBlockLog {
	block_name: string;
	context: {
		query_key: string;
		query_inputs: RemoteDataQueryInput[];
	};
	filtered_trace: QueryMonitorTrace[];
	level: string;
	message: string;
}

interface QueryMonitorData {
	'remote-data-blocks-logs'?: RemoteDataBlockLog[];
}

interface QueryMonitorTrace {
	file: string;
	line: number;
	function: string;
	class: string;
	type: string;
	id: string;
	display: string;
	calling_file: string;
	calling_line: number;
}
