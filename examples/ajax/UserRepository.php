<?php


namespace Dakujem\Selectoo\Examples;


/**
 * Example UserRepository
 *
 *
 * @author Andrej Rypák <xrypak@gmail.com> [dakujem](https://github.com/dakujem)
 */
class Dkj_UserRepository
{


	private function opts()
	{
		return [
			1 => 'Dakujem',
			2 => 'Andrej Rypak',
			3 => 'Hugo Ventil',
			4 => 'John Bull',
		];
	}


	/**
	 * Query users by name.
	 */
	public function queryUsers($query = null): array
	{
		//
		// Obviously, you would have some sort of a storage fetch call here,
		// the resulting call to a SQL DB would be similar to:
		//
		//
		//		'SELECT id, name FROM users WHERE name LIKE "%' .escape($query). '%"'
		//

		$pairs = $this->opts();

		if ($query) {
			return array_filter($pairs, function($v) use ($query) {
				return substr_compare($v, $query, 0, strlen($query), true) === 0;
			});
		}

		// return all options
		return $pairs;
	}


	/**
	 * Fetch users, optionally filtering by ID.
	 */
	public function fetchUsers($id = null): array
	{
		//
		// Obviously, you would have some sort of a storage fetch call here,
		// the resulting call to a SQL DB would be similar to:
		//
		//
		//		'SELECT id, name FROM users WHERE id = ' .escape($id). ' '
		//

		$pairs = $this->opts();

		// it is possible / enough to only return the selected value
		if ($id) {
			return array_intersect_key($pairs, [$id => true]);
		}

		// return all options
		return $pairs;
	}

}
